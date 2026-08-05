<div align="center">

# 🔔 Baichdo Notify

**A lightweight WordPress admin plugin that adds a "Send Notification" menu to push a message to every registered app device — reading RTCL's existing Expo push-token table.**

[![WordPress](https://img.shields.io/badge/WordPress-Plugin-21759B?logo=wordpress&logoColor=white)](https://wordpress.org/)
[![RTCL](https://img.shields.io/badge/RTCL-Push%20Tokens-orange)](https://wordpress.org/plugins/classified-listing/)
[![Expo](https://img.shields.io/badge/Expo-Push%20API-000020?logo=expo&logoColor=white)](https://docs.expo.dev/push-notifications/overview/)
[![PHP](https://img.shields.io/badge/PHP-7.4%2B-777BB4?logo=php&logoColor=white)](https://www.php.net/)
[![Version](https://img.shields.io/badge/Version-1.1.0-blue.svg)](#)
[![License](https://img.shields.io/badge/License-GPL--2.0%2B-blue.svg)](#-license)

<br>

<img src="docs/screenshots/admin.png" alt="Baichdo Notify send-notification admin screen" width="100%">

</div>

### 📸 Screenshots

<div align="center">
  <img src="docs/screenshots/1.png" alt="Send notification form" width="70%">
</div>

---

## 📖 Overview

**Baichdo Notify** adds a simple **"Send Notification"** page to the WordPress admin. From it you can broadcast an [Expo](https://docs.expo.dev/push-notifications/overview/) push notification to every device registered in your app — the plugin reads the existing `rtcl_push_notifications` token table (populated by the RTCL-based mobile app) and sends messages in batches through the Expo Push API.

> 💡 Purpose-built companion for the Baichdo mobile app — no separate dashboard or service required.

---

## ✨ Features

| Feature | Description |
|--------|-------------|
| 📣 **One-Click Broadcast** | Admin menu page to send a push to all devices |
| 🗂️ **Reads Existing Tokens** | Uses RTCL's `rtcl_push_notifications` table |
| 📦 **Batched Sending** | Sends in batches of 100 via the Expo Push API |
| 🔐 **Secure Admin Action** | Nonce-verified, `manage_options` capability gated |

---

## 🔄 How It Works

```mermaid
flowchart TD
    A[👨‍💼 Admin opens 'Send Notification'] --> B[Types title & message]
    B --> C{Nonce + capability<br/>verified?}
    C -- No --> X[🚫 Reject]
    C -- Yes --> D[Read tokens from<br/>rtcl_push_notifications]
    D --> E[Chunk into batches of 100]
    E --> F[POST to Expo Push API]
    F --> G[📲 Devices receive push]
```

---

## 🚀 Installation

1. Copy the `baichdo-notify-menu` folder to `/wp-content/plugins/`.
2. Activate **Baichdo Notify** under **Plugins**.
3. A **Send Notification** item (megaphone icon) appears in the admin menu.
4. Enter a title and message, then send — it reaches every registered device.

> ℹ️ Requires the RTCL push-token table (`{prefix}_rtcl_push_notifications`) to be populated by your app.

---

## 📁 Project Structure

```
baichdo-notify-menu/
└── baichdo-notify.php     # Admin menu page + Expo push sender
```

---

## ⚙️ Technical Notes

- **Expo endpoint:** `https://exp.host/--/api/v2/push/send`
- **Batch size:** 100 tokens per request
- **Security:** WordPress nonce verification + `manage_options` capability check

---

## 📝 License

Released under the **GPL-2.0-or-later** license.

---

<div align="center">
<sub>By <b>Ahmed Faran</b> · Companion plugin for the Baichdo app</sub>
</div>
