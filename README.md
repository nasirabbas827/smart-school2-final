# Smart School Portal

A PHP and MySQL portal connecting school administrators, teachers and students.

## Features

- Student, teacher and class administration
- Assignments, quizzes, exams, grades and study material
- Attendance, timetables and class chat
- Vouchers, transport, leave and feedback

## Technology

PHP, MySQL, HTML, CSS and JavaScript.

## Local setup

Clone the repository:

```bash
git clone https://github.com/nasirabbas827/smart-school2-final.git
cd smart-school2-final
```

1. Install a local PHP web server and MySQL, such as XAMPP.
2. Place the project in the server's document root, or configure a virtual host pointing to this folder.
3. Import `Database/schooldatabase.sql` using phpMyAdmin or your MySQL client.
4. Update the database connection in `config.php` and in role-specific copies of `config.php` to match your local database name and credentials.
5. Start Apache and MySQL, then open `http://localhost/smart-school2-final/`.

## Repository layout

| Path | Purpose |
|---|---|
| `CSS/` | Project resources |
| `Database/` | SQL database export |
| `admin/` | Role-specific pages |
| `config.php` | Database connection configuration |
| `images/` | Project resources |
| `index.php` | Web entry page |
| `teacher/` | Role-specific pages |
| `uploads/` | Project resources |

## Development notes

Setup steps describe local development. Configure your own database, mail and API credentials where applicable. Review debug settings and replace development secrets before deploying. The application was not executed as part of this documentation update.

Maintained by [Nasir Abbas](https://github.com/nasirabbas827).
