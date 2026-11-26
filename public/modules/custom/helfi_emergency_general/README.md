Custom module for Helfi Emergency website

## Functionality

1. This module provides a way for the admins to send an announcement message to the [Helfi static copy](https://kopio.hel.fi).
   - Adds edit Form at /admin/content/announcement-message
     where the message can be updated.
   - The message is stored in the azure storage account in the blob container
     set with `announcement_sas_url_base64_enc` variable.
