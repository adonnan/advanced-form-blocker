## Pardot Integration (Optional)

This plugin optionally allows integration with Salesforce Marketing Cloud Account Engagement (fka Pardot) to enforce the blocklist directly on your Pardot forms. To enable this, follow the steps below and then implement the provided (sanitized) script in your Pardot layout template.

**Step 1: Enable Pardot Integration in the Plugin**

1.  Navigate to the plugin settings page in your WordPress admin dashboard (**Admin > forms > Domain blocker**).
2.  Locate the "Pardot Integration" section.
3.  Check the box labeled "Enable Pardot Integration".
4.  Click **Save Changes**.

**Step 2: Implement the Sanitized Script in Your Pardot Layout Template**

The following JavaScript code should be placed within the `<form>` tags of your Pardot layout template, ideally just before the closing `</form>` tag. It may work if you paste it within the layout tab, but I prefer to use the form tab within Pardot's admin editor.

```html
<script type="text/javascript">
    jQuery(document).ready(function($) {
        const wordpressApiUrl = '[https://your-wordpress-site.com/wp-json/gf-domain-blocker/v1/blocked-list](https://your-wordpress-site.com/wp-json/gf-domain-blocker/v1/blocked-list)';
        let blockedDomains = [];
        let blockedEmails = [];
        let customMessages = {
            email: 'Your email address is blocked.',
            domain: 'Emails from your domain are blocked.'
        };
        let blocklistLoaded = false;
        const form = $('#pardot-form');

        function fetchBlocklist() {
            jQuery.ajax({
                url: wordpressApiUrl,
                method: 'GET',
                dataType: 'json',
                timeout: 8000,
                success: function(data) {
                    if (data && Array.isArray(data.domains) && Array.isArray(data.emails) && data.messages && typeof data.messages === 'object') {
                        blockedDomains = data.domains.map(domain => domain.toLowerCase());
                        blockedEmails = data.emails;
                        customMessages.email = data.messages.email || 'Your email address is blocked.';
                        customMessages.domain = data.messages.domain || 'Emails from your domain are blocked.';
                        blocklistLoaded = true;
                    } else {
                        blocklistLoaded = false;
                    }
                },
                error: function() {
                    blocklistLoaded = false;
                }
            });
        }

        fetchBlocklist();

        function showBlockError(message, fieldElement) {
            const fieldId = fieldElement.attr('id');
            const errorArea = $('#error_for_' + fieldId);
            const generalErrorArea = form.find('.errors');
            let targetErrorElement = null;

            if (errorArea.length) {
                targetErrorElement = errorArea;
            } else if (generalErrorArea.length) {
                targetErrorElement = generalErrorArea;
            }

            if (targetErrorElement) {
                targetErrorElement.text(message)
                    .addClass('pardot-block-error-message')
                    .css({
                        'color': 'red',
                        'font-size': '0.9em',
                        'margin-top': '5px',
                        'display': 'block'
                    })
                    .show();
                fieldElement.css('border-color', 'red');
            }
        }

        function clearBlockError(fieldElement) {
            const fieldId = fieldElement.attr('id');
            const errorArea = $('#error_for_' + fieldId);

            if (errorArea.length) {
                errorArea.text('').hide().removeClass('pardot-block-error-message').css({
                    'color': '', 'font-size': '', 'margin-top': '', 'display': ''
                });
            }
            fieldElement.css('border-color', '');
        }

        form.on('submit', function(e) {
            if (!blocklistLoaded) {
                return true;
            }

            const emailField = form.find('.gfield.email input[type="text"], .gfield.email input[type="email"], input[name*="pi_email"]');
            const emailInput = emailField.val();

            clearBlockError(emailField);

            let isBlocked = false;
            let blockMessage = '';

            if (emailInput) {
                if (blockedEmails.includes(emailInput)) {
                    isBlocked = true;
                    blockMessage = customMessages.email;
                }

                if (!isBlocked) {
                    const emailParts = emailInput.split('@');
                    if (emailParts.length === 2) {
                        const domain = emailParts[1].toLowerCase();
                        if (blockedDomains.includes(domain)) {
                            isBlocked = true;
                            blockMessage = customMessages.domain;
                        }
                    }
                }
            }

            if (isBlocked) {
                e.preventDefault();
                showBlockError(blockMessage, emailField);
                return false;
            } else {
                clearBlockError(emailField);
                return true;
            }
        });

        form.find('.gfield.email input[type="text"], .gfield.email input[type="email"], input[name*="pi_email"]').on('blur', function() {
            if (!blocklistLoaded) {
                return;
            }

            const emailField = $(this);
            const emailInput = emailField.val();

            clearBlockError(emailField);

            let isBlocked = false;
            let blockMessage = '';

            if (emailInput) {
                if (blockedEmails.includes(emailInput)) {
                    isBlocked = true;
                    blockMessage = customMessages.email;
                }

                if (!isBlocked) {
                    const emailParts = emailInput.split('@');
                    if (emailParts.length === 2) {
                        const domain = emailParts[1].toLowerCase();
                        if (blockedDomains.includes(domain)) {
                            isBlocked = true;
                            blockMessage = customMessages.domain;
                        }
                    }
                }
            }

            if (isBlocked) {
                showBlockError(blockMessage, emailField);
            } else {
                clearBlockError(emailField);
            }
        });

    });
</script>
