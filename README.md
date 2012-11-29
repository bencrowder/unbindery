## Unbindery

A web app for crowdsourcing transcription, written in PHP and JavaScript.

### Dependencies

* Twig (1.9.2 included)
* sfYaml (included)
* uploadify (2.1.4 included)
* MediaElement.js (2.10.0 included)
* ffmpeg (for audio transcription)

### Installation

1. Clone this repository or unpack the files.
2. Create a database and user in MySQL.
3. Copy `config.sample.yaml` to `config.yaml` and edit it.
4. If you want to transcribe audio, install ffmpeg and set the path to it in `config.yaml`.
5. Create the directory `htdocs/media` and give your web server (Apache, nginx, etc.) write rights to it.
6. Set your web server to point to `htdocs` for the site's document root.
7. In your `php.ini`, set `upload_max_filesize` to something big enough (`128M`, etc.).
8. In your `php.ini`, set `post_max_filesize` to something big enough (`128M`, etc.).
9. In your `php.ini`, set `max_file_uploads` to something big enough (`200`, etc.).
10. Go to `/install` in your browser.

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
