# WP Stop Forum Spam API

> “Guard the gate, share the vibe, keep the riff-raff out.”  

A nimble WordPress plugin that checks every anonymous visitor’s IP against the StopForumSpam.com database—using its confidence score to make sure you’re blocking true troublemakers, not curious wanderers. When an IP crosses your threshold (default ≥60%), the gate slams shut with a friendly 403… and if Wordfence is on watch, the block shows up in its Live Traffic log.

---

## ✨ Features

- **Confidence-Driven Blocking**  
  Queries `&confidence` on each IP, blocks only when SpamConfidence ≥60% (configurable in code).  
- **Transient Caching**  
  Caches each IP lookup for 1 hour, so repeat visitors don’t hammer the API—or your server.  
- **Wordfence Integration**  
  If Wordfence is active, every block is recorded in **Tools → Live Traffic** via `wfActivityReport::logBlockedIP()`.  
- **Skip the VIPs**  
  Automatically skips **logged-in users** and any `/wp-admin/` requests—because real friends don’t get bounced.  
- **Manual “Report IP” Form**  
  Under **Settings → Stop Forum Spam**, enter an IP, your email, and notes—your tip fuels the global spam database.  
- **WP Settings API & Best Practices**  
  Sanitization, nonces, escape functions, text-domain, function prefixes—built by the book, ready for custom hooks.

---

## 🚀 Installation

1. **Clone or download** into `wp-content/plugins/wilcosky-stop-forum-spam/`.  
2. **Activate** the plugin in your WP Admin.  
3. (Optional) Visit **Settings → Stop Forum Spam** and paste your StopForumSpam API key if you’d like to **report** IPs back.  

---

## 🎶 How It Works

1. **Hooked on `init`** (priority 1).  
2. **Checks** `is_user_logged_in()` and `is_admin()`—skips if true.  
3. **Looks up** `$_SERVER['REMOTE_ADDR']` in StopForumSpam with `&json&confidence`.  
4. **Caches** the `{ appears, confidence }` result for 1 hour.  
5. If `appears == 1 && confidence ≥ 60`:  
   - **Logs** the IP to Wordfence Live Traffic (if Wordfence exists).  
   - **Dies** with a 403 and a polite “Access denied” message.  

---

## 🛠️ Developer Guide

Feel free to:
- Adjust the **confidence threshold**.  
- Swap caching durations.  
- Hook into `wilcosky_stop_forum_spam_blocked_ip` (if you add that action) for custom alerts.  

---

## 💡 FAQs

**Q: What if StopForumSpam is down?**  
A: We **fail open**—no lookup means no block. Keeps your site flowing.

**Q: Can I whitelist certain IPs?**  
A: Yup! Add a quick `if ( $ip === '1.2.3.4' ) return;` in your child theme or custom plugin before the check.

**Q: Will logged-in admins ever get blocked?**  
A: Never—in code we trust our logged-in users implicitly.

---

## 🤝 Contributing

Bugs, ideas, cosmic suggestions? PRs and issues are welcome. Let’s keep the vibe strong, the code clean, and the Internet a little safer for everyone.
