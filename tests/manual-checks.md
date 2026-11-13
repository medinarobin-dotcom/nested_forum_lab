# Manual checks (lab step 8)

Start the server first: `php -S localhost:8000 -t public public/index.php`

| # | Check | How | Expected |
|---|---|---|---|
| 1 | Read as guest | Open `/discussions/1` | Tree shows Alice > Ben > Cara > Ben > Eli (depth 0–4), Dee under Alice, Eli at depth 0. No reply forms. |
| 2 | Register | `/register` with a new username | Redirect to home, "Signed in as …" |
| 3 | Duplicate account | Register same username/email again | "already registered" (422) |
| 4 | Wrong password | Log in with a bad password | "Invalid username or password." (401) |
| 5 | Seed user | Log in as `Alice` with empty password | Rejected (NULL hash never logs in) |
| 6 | Depth-5 reply | Logged in, click **Reply** under Eli's "Add replies next." | New comment shows "Depth 5" under Eli |
| 7 | Second top-level | Use "Add a top-level comment" | New comment at depth 0 with your name |
| 8 | Guest POST (UI) | Log out, open `/discussions/1` | No reply forms shown |
| 9 | Guest POST (direct) | `curl -i -X POST -d "body=hi" localhost:8000/discussions/1/comments` | 303 redirect to `/login`, no new row |
| 10 | Invalid CSRF | Logged in, edit the hidden `csrf_token` in DevTools, submit | 403, no new row |
| 11 | Cross-discussion parent | Logged in, change a reply form's action to `/discussions/1/comments/8/replies` (comment 8 is in discussion 2) | 400, no new row |
| 12 | Missing discussion | Open `/discussions/999` | 404 |
| 13 | Malformed ID | Open `/discussions/abc` | 400 |
| 14 | HTML escaping | Post `<script>alert('x')</script>` | Shown as plain text, no alert |
| 15 | Collapse (optional) | Click "Hide replies" on Alice | Ben, Cara, Dee hidden; Eli visible; label + `aria-expanded` change |

Check rows in MySQL: `SELECT c.id, c.parent_id, u.username, c.body FROM comments c JOIN users u ON u.id = c.user_id ORDER BY c.id;`
