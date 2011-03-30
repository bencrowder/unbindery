## Unbindery

A book digitization web app.

### Installation

1. Create a MySQL database and a user for it.
2. Edit `db.sql` to reference that database.
3. Run `db.sql` (`mysql -u root -p < db.sql`).
4. Copy `include/config.sample.php` to `include/config.php` and customize it.
5. Copy `include/alibaba_config.sample.php` to `include/alibaba_config.php` and customize it.
6. Copy `include/guidelines.sample.php` to `include/guidelines.php` and customize it.
7. Copy `js/config.sample.js` to `js/config.js` and customize it.
8. Create a directory `images/`.
8. Give Apache rights to write to `images/` (`chgrp apache images`, `chmod g+w images`).
9. Set up the cronjob (we recommend `1 0 * * * php /path/to/cron.php`, running every day at 12:01 a.m.) -- the cronjob docks points from people past deadline and emails people when their deadline is coming up.
10. Install [Ace](http://github.com/ajaxorg/ace) and put it in `lib/ace`.
