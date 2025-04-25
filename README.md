# WP Stop Forum Spam API

Connect to StopForumSpam's API to block and/or report malicious IPs.

## Description

The WP Stop Forum Spam API plugin integrates with the [StopForumSpam](https://www.stopforumspam.com/) service to help protect your WordPress site from spam and malicious IPs. It allows you to check visitor IPs against the StopForumSpam database and take appropriate action.

### What's New in Version 1.2
- Added the `/ ?testsfs= ` query parameter for easier testing during development.

## Features
- Query the StopForumSpam database to identify spammy or malicious IPs.
- Block IPs with a high confidence score.
- Cache results for improved performance.
- Easy testing with the `/ ?testsfs= ` query parameter.

## Installation

1. Download the plugin zip file from [Releases](https://github.com/zerosonesfun/wp-stop-forum-spam-api/releases).
2. Upload the zip file to your WordPress site through the Plugins section in the admin dashboard.
3. Activate the plugin.

## Usage

The plugin works automatically once activated:
- It checks visitor IPs against the StopForumSpam database.
- Blocks IPs with a high confidence score (≥ 60%).

To test the plugin:
1. Add `/?testsfs=<IP>` to your site's URL (replace `<IP>` with the IP address you want to test).
2. View the results or debug any potential issues.

## How It Works
1. The plugin retrieves the visitor's IP address.
2. It queries the StopForumSpam API to check the IP's reputation.
3. If the API indicates the IP is spammy with a confidence score of 60% or higher, the plugin blocks the IP.

## Changelog

### [1.2](https://github.com/zerosonesfun/wp-stop-forum-spam-api/releases/tag/1.2)
- Added the `/ ?testsfs= ` query parameter for testing.

### [1.1.7](https://github.com/zerosonesfun/wp-stop-forum-spam-api/releases/tag/1.1.7)
- Initial public release.
