## Unbindery

A book digitization web app.

### Installation

1. Create a MySQL database.
2. Edit `db.sql` to reference that database.
3. Run `db.sql` (`mysql -p < db.sql`)
4. Copy `include/config.sample.php` to `include/config.php` and customize it.
5. Copy `include/alibaba_config.sample.php` to `include/alibaba_config.php` and customize it.
6. Copy `include/guidelines.sample.php` to `include/guidelines.php` and customize it.
3. Give Apache rights to write to images/ (`chgrp apache images`, `chmod g+w images`).
4. Set up cronjob (we recommend `1 0 * * * php /path/to/cron.php`, running every day at 12:01 a.m.) -- the cronjob docks points from people past deadline and emails people when their deadline is coming up.
5. Install [Ace](http://github.com/ajaxorg/ace) and put it in lib/ace.
