
ALTER TABLE `ddns_token`
    ADD COLUMN `server_id` int(11) unsigned NOT NULL DEFAULT 0 AFTER id;
