# TODO — kova-kamsv2 refactor

## 1 Goals
1.1 Consolidate repository under `kova/`  
1.2 Serve web from `/srv/kova/unified` (symlinked to `/var/www/html`)  
1.3 Keep KCM code and cron/worker scripts outside webroot  
1.4 Consolidate autoloads to single root `vendor/autoload.php`  
1.5 Provide deploy helper and systemd timers for KCM jobs

## 2 Status key
2.1 [x] done  
2.2 [ ] pending  
2.3 [~] in progress / partially done  
2.4 [!] blocked / needs attention

## 3 High priority
3.1 [x] Create branch `refactor/consolidate`  
3.2 [x] Backup current webroot: `cp -r /var/www/html /var/www/html-bak`  
3.3 [x] Remove previous `/var/www/html` (backup kept) `rm -rf /var/www/html`  
3.4 [x] Move `unified` -> `kova/unified` and `KCM` -> `kova/kcm` within repo  
3.5 [ ] Add `kova/app/bootstrap.php` with `KOVA_ROOT` and single autoload require  
3.6 [ ] Replace nested `vendor/autoload.php` requires with bootstrap in entry points  
3.7 [ ] Create `scripts/deploy-symlink.sh` to setup `/srv/kova` and symlink `/var/www/html`  
3.8 [ ] Add runtime dirs: `/srv/kova/logs`, `/srv/kova/tmp`, `/srv/kova/unified/uploads`  
3.9 [ ] Commit and push branch, open PR for review

## 4 Medium priority
4.1 [ ] Create root `composer.json` merging autoload rules; run `composer install`  
4.2 [ ] Remove or archive nested `vendor/` directories  
4.3 [ ] Add systemd unit + timer templates for KCM jobs  
4.4 [ ] Add CI step to run composer at repo root and cache `vendor/`  
4.5 [ ] Add README deploy and rollback instructions

## 5 Low priority / cleanup
5.1 [ ] Audit code for remaining hard-coded absolute paths  
5.2 [ ] Add tests or smoke scripts to validate bootstrap and KCM scripts  
5.3 [ ] Add logrotate config for `/srv/kova/logs`  
5.4 [ ] Add opcache clear hook to deployment

## 6 Notes / context
6.1 Webserver: nginx or apache (document which)  
6.2 PHP-FPM pool user: (e.g., www-data)  
6.3 KCM service user: (e.g., kcm)  
6.4 Preferred deploy path: `/srv/kova`  
6.5 Current remote name(s): `github` (verify remote target before pushing)  
6.6 Sensitive files: keep `.env` and secrets outside repo (in `/srv/kova/config` or secret store)

## 7 Next concrete steps (execution)
7.1 Ensure on refactor branch: `git checkout refactor/consolidate`  
7.2 Create `kova/` and move tracked directories (preserve history):  
    - `mkdir -p kova`  
    - `git add kova`  
    - `git mv unified kova/unified`  
    - `git mv KCM kova/kcm`  
    - `git commit -m "Refactor: consolidate unified and KCM under kova/"`  
7.3 Add bootstrap: create `kova/app/bootstrap.php` (define `KOVA_ROOT`, require root autoload) and commit  
7.4 Replace entry-point autoloads to require bootstrap (front controller, KCM scripts) and commit  
7.5 Add deploy helper script `scripts/deploy-symlink.sh`, make executable, commit  
7.6 Run deploy helper on server to create symlink and runtime dirs: `sudo /srv/kova/scripts/deploy-symlink.sh`  
7.7 Verify site and KCM jobs: `curl -I http://localhost/`, tail logs, run a KCM job as its user  
7.8 Create root composer.json, merge autoloads, run `composer install`, remove nested vendors

## 8 Verification checklist
8.1 Public document root limited to `kova/unified` (or `unified/public`)  
8.2 All entry points include `kova/app/bootstrap.php`  
8.3 No remaining nested `vendor/autoload.php` requires  
8.4 Runtime dirs exist and writable by `www-data` as appropriate  
8.5 Systemd timers or cron entries for KCM updated to use `KOVA_ROOT`  
8.6 CI updated to run composer at root and artifacts cached

## 9 Rollback plan
9.1 Restore `/var/www/html` from `/var/www/html-bak` if needed:  
    - `rm /var/www/html && mv /var/www/html-bak /var/www/html`  
9.2 Stop KCM timers/services and restore previous repo state via git on the server  
9.3 Restart php-fpm to clear opcache if necessary: `systemctl restart php*-fpm`
