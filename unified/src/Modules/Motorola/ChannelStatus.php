<?php

declare(strict_types=1);

namespace Kova\Kams\Unified\Modules\Motorola;

use Kova\Kams\Common\Database;
use Kova\Kams\Common\Config;

/**
 * ChannelStatus
 * 
 * Provides Motorola channel status display similar to v1/channels/channels-check-ajax.php
 * but using v2 standards (DBAL, namespaces, etc.)
 */
class ChannelStatus
{
    private Database $db;
    private Config $config;
    private int $timeNow;

    public function __construct(?Database $db = null, ?Config $config = null)
    {
        $this->db = $db ?? new Database('kams');
        $this->config = $config ?? new Config();
        $this->timeNow = time();
    }

    /**
     * Render the full channel status page
     * Includes warnings, active channels, and unmonitored channels
     * 
     * @return string HTML content
     */
    public function renderStatus(): string
    {
        $output = '';
        
        // Warnings section
        $output .= $this->renderWarnings();
        
        // Active channels section
        $output .= $this->renderActiveChannels();
        
        // Unmonitored channels section
        $output .= $this->renderUnmonitoredChannels();
        
        return $output;
    }

    /**
     * Render warnings section (channels with timeout issues)
     * 
     * @return string HTML
     */
    private function renderWarnings(): string
    {
        $output = '<div class="moto-section">';
        $output .= '<div class="moto-section-title warning">Warnings</div>';
        $output .= '<div class="moto-channels-grid">';

        // Get all monitored channels
        $qb = $this->db->createQueryBuilder();
        $qb->select('*')
           ->from('moto_channel_data')
           ->where('timeout_number <> :zero')
           ->setParameter('zero', 0)
           ->orderBy('last_activity', 'DESC')
           ->addOrderBy('channel_id', 'ASC');
        
        $channels = $this->db->executeQueryBuilder($qb);
        
        $issues = 0;
        
        foreach ($channels as $channel) {
            $channelId = (int)($channel['channel_id'] ?? 0);
            $channelName = htmlspecialchars((string)($channel['channel_name'] ?? 'Unknown'), ENT_QUOTES, 'UTF-8');
            $lastActivity = (int)($channel['last_activity'] ?? 0);
            $timeoutNumber = (int)($channel['timeout_number'] ?? 0);
            $timeoutValue = (string)($channel['timeout_value'] ?? 'Hours');
            
            // Calculate time threshold
            $timeCheck = $this->calculateTimeThreshold($timeoutNumber, $timeoutValue);
            
            // Check if channel has issues
            if ($lastActivity == 0 || $lastActivity == '') {
                // No activity ever recorded
                $timeValue = $timeoutNumber == 1 ? substr_replace($timeoutValue, "", -1) : $timeoutValue;
                $output .= "<button type='button' class='moto-channel-btn danger'>{$channelName}<br>Timeout {$timeoutNumber} {$timeValue}<br>NO ACTIVITY</button>";
                $issues++;
            } elseif ($lastActivity < $timeCheck) {
                // Activity older than timeout
                $timeValue = $timeoutNumber == 1 ? substr_replace($timeoutValue, "", -1) : $timeoutValue;
                $lastActivityFormatted = date('Y-m-d h:i:s', $lastActivity);
                $output .= "<button type='button' class='moto-channel-btn warning'>{$channelName}<br>{$lastActivityFormatted}<br>Timeout {$timeoutNumber} {$timeValue}</button>";
                $issues++;
            }
        }
        
        if ($issues == 0) {
            $output .= '<div class="moto-status-ok">No Issues - System is OK</div>';
        }
        
        $output .= '</div></div>';
        return $output;
    }

    /**
     * Render active channels section
     * 
     * @return string HTML
     */
    private function renderActiveChannels(): string
    {
        $output = '<div class="moto-section">';
        $output .= '<div class="moto-section-title active">Active Channels</div>';
        $output .= '<div class="moto-channels-grid">';
        
        // Get all monitored channels
        $qb = $this->db->createQueryBuilder();
        $qb->select('*')
           ->from('moto_channel_data')
           ->where('timeout_number <> :zero')
           ->setParameter('zero', 0)
           ->orderBy('last_activity', 'DESC')
           ->addOrderBy('channel_id', 'ASC');
        
        $channels = $this->db->executeQueryBuilder($qb);
        
        foreach ($channels as $channel) {
            $channelId = (int)($channel['channel_id'] ?? 0);
            $channelName = htmlspecialchars((string)($channel['channel_name'] ?? 'Unknown'), ENT_QUOTES, 'UTF-8');
            $lastActivity = (int)($channel['last_activity'] ?? 0);
            $timeoutNumber = (int)($channel['timeout_number'] ?? 0);
            $timeoutValue = (string)($channel['timeout_value'] ?? 'Hours');
            
            // Calculate time threshold
            $timeCheck = $this->calculateTimeThreshold($timeoutNumber, $timeoutValue);
            
            // Show if channel is active (recent activity)
            if ($lastActivity > $timeCheck && $lastActivity != 0) {
                $timeValue = $timeoutNumber == 1 ? substr_replace($timeoutValue, "", -1) : $timeoutValue;
                $lastActivityFormatted = date('Y-m-d h:i:s', $lastActivity);
                $output .= "<button type='button' class='moto-channel-btn success'>{$channelName}<br>{$lastActivityFormatted}<br>Timeout {$timeoutNumber} {$timeValue}</button>";
            }
        }
        
        $output .= '</div></div>';
        return $output;
    }

    /**
     * Render unmonitored channels section
     * 
     * @return string HTML
     */
    private function renderUnmonitoredChannels(): string
    {
        $output = '<div class="moto-section">';
        $output .= '<div class="moto-section-title unmonitored">Unmonitored Channels</div>';
        $output .= '<div class="moto-channels-grid">';
        
        // Get unmonitored channels (timeout_number = 0)
        $qb = $this->db->createQueryBuilder();
        $qb->select('*')
           ->from('moto_channel_data')
           ->where('timeout_number = :zero')
           ->setParameter('zero', 0)
           ->orderBy('last_activity', 'DESC')
           ->addOrderBy('channel_id', 'ASC');
        
        $channels = $this->db->executeQueryBuilder($qb);
        
        foreach ($channels as $channel) {
            $channelId = (int)($channel['channel_id'] ?? 0);
            $channelName = htmlspecialchars((string)($channel['channel_name'] ?? 'Unknown'), ENT_QUOTES, 'UTF-8');
            $lastActivity = (int)($channel['last_activity'] ?? 0);
            
            $lastActivityFormatted = $lastActivity > 0 ? date('Y-m-d h:i:s', $lastActivity) : 'No Activity';
            $output .= "<button type='button' class='moto-channel-btn secondary'>{$channelName}<br>{$lastActivityFormatted}</button>";
        }
        
        $output .= '</div></div>';
        return $output;
    }

    /**
     * Calculate time threshold based on timeout settings
     * 
     * @param int $timeoutNumber Timeout number
     * @param string $timeoutValue Timeout unit (Hours, Days, Weeks)
     * @return int Timestamp threshold
     */
    private function calculateTimeThreshold(int $timeoutNumber, string $timeoutValue): int
    {
        $timeoutValue = ucfirst(strtolower(trim($timeoutValue)));
        
        switch ($timeoutValue) {
            case 'Hours':
                return $this->timeNow - ($timeoutNumber * 3600);
            case 'Days':
                return $this->timeNow - ($timeoutNumber * 86400);
            case 'Weeks':
                return $this->timeNow - ($timeoutNumber * 604800);
            default:
                // Default to hours if unknown
                return $this->timeNow - ($timeoutNumber * 3600);
        }
    }
}

