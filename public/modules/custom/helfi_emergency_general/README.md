Custom module for Helfi Emergency website

## About
This module covers custom general functionality for the website - small functionalities that are not worth creating separate modules for.
Separation will be necessary when/if current small features will be further developed.

## Current functionality

1. This module provides a method for the user to accept the cookies set by iFrames in the page by enabling the custom "Filter Iframe"
CKEditor filter. It uses javascript to handle the logic.

2. The other functionality of this module is to provide a way for the admins to send an announcement message to the Helfi static copy
application to be displayed there.
 - There is a config form created at /admin/content/announcement-message where you can see the current message and update it.
 - The message is stored in the azure storage account in the blob container that is set using env vars.
