## Unbindery

A book digitization web app written in PHP and JavaScript.

### Dependencies

* Twig (1.6.0 included)
* sfYaml (included)
* uploadify (included)

### Installation

1. Create a MySQL database and a user for it.
2. Copy `modules/db/db.sql` to `import.sql` and edit it to reference that database.
3. Run `import.sql` (`mysql -u root -p < import.sql`).
4. Copy `config.sample.yaml` to `config.yaml` and customize it.
5. Copy `scripts/config.sample.py` to `scripts/config.py` and customize it.
6. Create a directory `htdocs/media/`.
7. Give Apache rights to write to `htdocs/media/` (`chgrp apache htdocs/media`, `chmod g+w htdocs/media`).
8. Set up the cronjob (we recommend `1 0 * * * php /path/to/cron.php`, running every day at 12:01 a.m.) -- the cronjob docks points from people past deadline and emails people when their deadline is coming up.
9. Set Apache to point to the `htdocs` directory as root.

### Customization

#### Database Engine

* MySQL

#### Authentication Engine

* Alibaba

#### Item Types

* Page
* Audio
