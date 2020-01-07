
# simpletracker

`simpletracker` is a minimal implementation of a private BitTorrent tracker,
written in PHP. It supports both the PostgreSQL and MySQL backends. Features include

- uploading, downloading, listing, and viewing torrent files (including their contents),
- a minimal tracker to communicate with BitTorrent clients,
- user login, logout, and registration,
- an invitation system, and
- ... actually, that's all. Yes, it's very minimial.

## Installation

Here's a terse walkthrough for the tech-savy:

1. Recreate the database by using `db.mysql.sql` or `db.pgsql.sql`, depending on
   if you're using PostgreSQL or MySQL.
2. Change the configuration in `config.php` as needed. At the very least you need
   to change `base_url`, and possibly `db` as well (unless you're using the
   defaults used in the sql files).
3. Configure your web server (i.e. nginx or Apache) to serve the `serve`
   directory from the path you configured in `config.php` (i.e. `base_url`).
4. If you navigate to the url, you should now see a login form. If not, your *web
   server* is misconfigured.
5. Log in with username `simpletracker` and password `simpletracker`. If
   that fails, your *database* is misconfigured.
6. Go to invitations, and make yourself an invitation. Follow the invitation
   link, and register for an account.
7. Manually remove the `simpletracker` account from the users table.

The code has been tested on an Arch Linux server, using nginx (1.10.1), and both
MySQL (10.1.18-MariaDB) and PostgreSQL (9.5.4). Users have also reported the
code working on Windows. Your mileage may vary.

