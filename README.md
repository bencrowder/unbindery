## Unbindery

A web app for crowdsourcing transcription, written in PHP and JavaScript.

### Dependencies

* Twig (1.9.2 included)
* sfYaml (included)
* uploadify (included)
* MediaElement.js (included)
* ffmpeg (for audio transcription)

### Installation

1. Create a database and user in MySQL.
2. Copy `config.sample.yaml` to `config.yaml` and edit it.
3. Create the directory `htdocs/media` and give Apache write rights to it.
4. Set Apache to point to `htdocs` for the site's `DocumentRoot`.
5. In your `php.ini`, set `upload_max_filesize` to something big enough (`128M`, etc.).
6. In your `php.ini`, set `post_max_filesize` to something big enough (`128M`, etc.).
7. In your `php.ini`, set `max_file_uploads` to something big enough (`200`, etc.).
8. Go to `/install` in your browser.

### Customization

#### Database Engine

* MySQL

#### Authentication Engine

* Alibaba

#### Item Types

* Page
* Audio

### Acknowledgments

* Thanks to [Ryan Martinsen](http://twitter.com/popthestack) for his [fork](https://github.com/popthestack/PHP-FineDiff) of Raymond Hill's [FineDiff](https://github.com/gorhill/PHP-FineDiff).
