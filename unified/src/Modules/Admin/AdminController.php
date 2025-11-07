<?php

declare(strict_types=1);

namespace Kova\Kams\Unified\Modules\Admin;

use Kova\Kams\Common\Database;
use Kova\Kams\Unified\Modules\Common\Config;

/**
 * AdminController
 * 
 * Handles admin page rendering and settings management
 */
class AdminController
{
    private Database $db;
    private array $config;

    public function __construct(?Config $config = null)
    {
        $this->db = new Database('kams');
        $configObj = $config ?? new Config();
        $this->config = $configObj->config;
    }

    /**
     * Render the admin page
     * Reads admin.html and replaces API path
     */
    public function renderAdminPage(): string
    {
        $adminHtmlPath = __DIR__ . '/admin.html';
        
        // Check if admin.html exists
        if (!file_exists($adminHtmlPath)) {
            // Return a simple admin page if file doesn't exist
            return $this->getSimpleAdminPage();
        }
        
        $html = file_get_contents($adminHtmlPath);
        
        // Update API path to work when loaded via AJAX (multiple possible formats)
        $html = preg_replace(
            "/const\s+API\s*=\s*['\"][^'\"]*api\.php['\"];?/",
            "const API = 'src/Modules/Admin/api.php';",
            $html
        );
        
        return $html;
    }

    /**
     * Render the superadmin page
     */
    public function renderSuperadminPage(): string
    {
        $superadminHtmlPath = __DIR__ . '/superadmin.html';
        
        // Check if superadmin.html exists
        if (!file_exists($superadminHtmlPath)) {
            return '<div style="padding:20px;color:red;">Superadmin page not found</div>';
        }
        
        $html = file_get_contents($superadminHtmlPath);
        
        // Update API path to work when loaded via AJAX
        $html = preg_replace(
            "/const\s+API\s*=\s*['\"][^'\"]*superadmin_api\.php['\"];?/",
            "const API = 'src/Modules/Admin/superadmin_api.php';",
            $html
        );
        
        return $html;
    }

    /**
     * Render the cron services page
     */
    public function renderCronServicesPage(): string
    {
        $servicesHtmlPath = __DIR__ . '/admin-services.html';
        
        if (!file_exists($servicesHtmlPath)) {
            return '<div style="padding:20px;color:red;">Cron services page not found</div>';
        }
        
        $html = file_get_contents($servicesHtmlPath);
        
        // Update API path to work when loaded via AJAX
        $html = preg_replace(
            "/const\s+API\s*=\s*['\"][^'\"]*api\.php['\"];?/",
            "const API = 'src/Modules/Admin/api.php';",
            $html
        );
        
        return $html;
    }

    /**
     * Render the utilities page
     */
    public function renderUtilitiesPage(): string
    {
        $utilitiesHtmlPath = __DIR__ . '/admin-utilities.html';
        
        if (!file_exists($utilitiesHtmlPath)) {
            return '<div style="padding:20px;color:red;">Utilities page not found</div>';
        }
        
        $html = file_get_contents($utilitiesHtmlPath);
        
        // Update API path to work when loaded via AJAX
        $html = preg_replace(
            "/const\s+API\s*=\s*['\"][^'\"]*api\.php['\"];?/",
            "const API = 'src/Modules/Admin/api.php';",
            $html
        );
        
        return $html;
    }

    /**
     * Get simple admin page if admin.html doesn't exist
     */
    private function getSimpleAdminPage(): string
    {
        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin - Settings Management</title>
    <style>
        body {
            font-family: system-ui, -apple-system, sans-serif;
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .admin-container {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #e0e0e0;
        }
        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            background: #007bff;
            color: white;
            text-decoration: none;
            display: inline-block;
        }
        .btn:hover {
            background: #0056b3;
        }
        .btn.secondary {
            background: #6c757d;
        }
        .message {
            padding: 12px;
            margin-bottom: 20px;
            border-radius: 4px;
            display: none;
        }
        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        #settingsContainer {
            margin-top: 20px;
        }
        .setting-row {
            display: grid;
            grid-template-columns: 1fr 2fr 120px;
            gap: 10px;
            padding: 10px;
            border-bottom: 1px solid #e0e0e0;
            align-items: center;
        }
        .setting-row:hover {
            background: #f8f9fa;
        }
        .setting-name {
            font-weight: 600;
        }
        .setting-value {
            padding: 6px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .setting-actions {
            display: flex;
            gap: 5px;
        }
        .btn-small {
            padding: 4px 8px;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="header">
            <h1>Settings Admin</h1>
            <div>
                <button class="btn secondary" onclick="window.location.reload()">Refresh</button>
                <button class="btn secondary" onclick="logout()">Logout</button>
            </div>
        </div>
        
        <div id="message" class="message"></div>
        
        <div id="settingsContainer">
            <p>Loading settings...</p>
        </div>
    </div>

    <script src="src/Bones/js/jquery.min.js"></script>
    <script>
        const API = 'src/Modules/Admin/api.php';
        
        // Check authentication
        checkAuth();
        
        function checkAuth() {
            fetch(API + '?action=check', {
                credentials: 'same-origin'
            })
            .then(r => r.json())
            .then(data => {
                if (!data.authenticated) {
                    showLoginModal();
                } else {
                    loadSettings();
                }
            });
        }
        
        function showLoginModal() {
            const password = prompt('Enter admin password:');
            if (password) {
                fetch(API + '?action=login', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    credentials: 'same-origin',
                    body: JSON.stringify({password: password})
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success || data.error === undefined) {
                        loadSettings();
                    } else {
                        alert('Invalid password');
                        window.location.reload();
                    }
                });
            }
        }
        
        function loadSettings() {
            fetch(API, {
                credentials: 'same-origin'
            })
            .then(r => r.json())
            .then(data => {
                if (data.success && data.data) {
                    renderSettings(data.data);
                } else {
                    document.getElementById('settingsContainer').innerHTML = '<p>Error loading settings</p>';
                }
            });
        }
        
        function renderSettings(settings) {
            const container = document.getElementById('settingsContainer');
            container.innerHTML = '<div class="setting-row"><strong>Name</strong><strong>Value</strong><strong>Actions</strong></div>';
            
            settings.forEach(setting => {
                const row = document.createElement('div');
                row.className = 'setting-row';
                row.innerHTML = \`
                    <div class="setting-name">\${setting.name || setting.setname || ''}</div>
                    <input type="text" class="setting-value" value="\${setting.value || setting.setvalue || ''}" 
                           id="val_\${setting.id}" data-id="\${setting.id}">
                    <div class="setting-actions">
                        <button class="btn btn-small" onclick="saveSetting(\${setting.id})">Save</button>
                    </div>
                \`;
                container.appendChild(row);
            });
        }
        
        function saveSetting(id) {
            const value = document.getElementById('val_' + id).value;
            const setting = {value: value};
            
            fetch(API + '?id=' + id, {
                method: 'PUT',
                headers: {'Content-Type': 'application/json'},
                credentials: 'same-origin',
                body: JSON.stringify(setting)
            })
            .then(r => {
                if (r.status === 204) {
                    showMessage('Setting saved successfully', 'success');
                } else {
                    showMessage('Error saving setting', 'error');
                }
            });
        }
        
        function logout() {
            fetch(API + '?action=logout', {
                method: 'POST',
                credentials: 'same-origin'
            })
            .then(() => {
                if (typeof getHome === 'function') {
                    getHome();
                } else {
                    window.location.reload();
                }
            });
        }
        
        function showMessage(text, type) {
            const msg = document.getElementById('message');
            msg.textContent = text;
            msg.className = 'message ' + type;
            msg.style.display = 'block';
            setTimeout(() => msg.style.display = 'none', 5000);
        }
    </script>
</body>
</html>
HTML;
    }

    /**
     * Get settings for admin page
     */
    public function getSettings(): array
    {
        $qb = $this->db->createQueryBuilder();
        $qb->select('*')
           ->from('settings')
           ->orderBy('COALESCE(group_name, "default")', 'ASC')
           ->addOrderBy('COALESCE(sort_order, 100)', 'ASC')
           ->addOrderBy('name', 'ASC');
        
        return $this->db->executeQueryBuilder($qb);
    }

    /**
     * Update a setting
     */
    public function updateSettings(int $id, array $data): bool
    {
        $qb = $this->db->createQueryBuilder();
        $qb->update('settings');
        
        if (isset($data['name'])) {
            $qb->set('name', ':name')->setParameter('name', $data['name']);
        }
        if (isset($data['value'])) {
            $qb->set('value', ':value')->setParameter('value', $data['value']);
        }
        if (isset($data['type'])) {
            $qb->set('type', ':type')->setParameter('type', $data['type']);
        }
        if (isset($data['description'])) {
            $qb->set('description', ':description')->setParameter('description', $data['description']);
        }
        
        $qb->where('id = :id')->setParameter('id', $id);
        
        $this->db->executeQueryBuilder($qb);
        return true;
    }
}

