
ALTER TABLE `ddns_token`
    ADD COLUMN `server_id` int(11) unsigned NOT NULL DEFAULT 0 AFTER id;

select @server_id := server_id from server
where active=1 and dns_server=1
order by server_id
limit 1;

update ddns_token set server_id = @server_id
where server_id = 0;

update sys_datalog set server_id = @server_id
where server_id = 0 and dbtable = 'ddns_token';
