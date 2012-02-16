## Unbindery

A book digitization web app written in PHP and JavaScript.

### Dependencies

* Twig
* sfyaml
* uploadify

### Installation

1. Create a MySQL database and a user for it.
2. Copy `db/db.sql` to `import.sql` and edit it to reference that database.
3. Run `import.sql` (`mysql -u root -p < import.sql`).
4. Copy `include/config.sample.yaml` to `include/config.yaml` and customize it.
5. Copy `scripts/config.sample.py` to `scripts/config.py` and customize it.
6. Create a directory `media/`.
7. Give Apache rights to write to `media/` (`chgrp apache media`, `chmod g+w media`).
8. Set up the cronjob (we recommend `1 0 * * * php /path/to/cron.php`, running every day at 12:01 a.m.) -- the cronjob docks points from people past deadline and emails people when their deadline is coming up.

### Customization

#### Database Engine

* MySQL

#### Authentication Engine

* Alibaba

#### Item Types

* Page
* Audio
