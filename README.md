## Unbindery

A book digitization app.

### Installation

1. Run MySQL script (need to create).
2. Customize `include/config.php` and `include/alibaba\_config.php`
3. Give Apache rights to write to images/ (`chgrp apache images`, `chmod g+w images`).
4. Set up cronjob (we recommend `1 0 * * * php /path/to/cron.php`, running every day at 12:01 a.m.) -- the cronjob docks points from people past deadline and emails people when their deadline is coming up
5. Install [Ace](http://github.com/ajaxorg/ace) and put it in lib/ace.
