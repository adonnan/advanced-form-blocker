# Pardot Integration
## Layout Template Layout Tab
### (change YOURBASEURL)
```
<!DOCTYPE html>
<html>
<head>
    <base href="YOURBASEURL">
    <meta charset="utf-8"/>
    <meta name="description" content="%%description%%"/>
    <meta name="viewport" content="width=device-width,initial-scale=1.0"/>
    <title>%%title%%</title>

    <!-- LOAD JQUERY HERE -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js"></script>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700&family=Open+Sans:wght@400;600&display=swap" rel="stylesheet">

    <!-- Keep Bootstrap for form styling base and utilities if needed, but we'll override a lot -->
    <link rel="stylesheet" type="text/css" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
    
    <style>
        /* --------------------------------------------------------------
            GLOBAL & RESET STYLES
        ----------------------------------------------------------------- */
        body {
            font-family: 'Open Sans', sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f9f9f9;
            margin: 0;
            padding: 0;
        }

        /* Box-sizing for all elements */
        *,
        *:before,
        *:after {
            -webkit-box-sizing: border-box;
            -moz-box-sizing: border-box;
            box-sizing: border-box;
        }

        h1, h2, h3, h4, h5, h6 {
            font-family: 'Montserrat', sans-serif;
            color: #1a237e; /* A modern dark blue */
            margin-top: 1.5em;
            margin-bottom: 0.5em;
        }
        h1 { font-size: 2.2em; }
        h2 { font-size: 1.8em; }
        h3 { font-size: 1.4em; }

        p {
            margin-bottom: 1em;
        }

        a {
            color: #3f51b5; /* A complementary blue */
            text-decoration: none;
        }
        a:hover {
            text-decoration: underline;
        }

        img {
            max-width: 100%;
            height: auto;
            display: block; /* Removes bottom space under image */
        }

        ul, ol {
            margin-bottom: 1em;
            padding-left: 20px;
        }
        ul li, ol li {
            margin-bottom: 0.5em;
        }

        /* --------------------------------------------------------------
            LAYOUT & CONTAINER
        ----------------------------------------------------------------- */
        .page-wrapper {
            max-width: 800px; /* Max width for content readability */
            margin: 0 auto;
            background-color: #fff;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
        }

        .content-section {
            padding: 20px 30px;
        }
        @media (max-width: 767px) {
            .content-section {
                padding: 20px 15px;
            }
        }

        /* --------------------------------------------------------------
            HEADER
        ----------------------------------------------------------------- */
        .header {
            padding: 20px 30px;
            border-bottom: 1px solid #eee;
            text-align: center; /* Center logo and CTA on all screens */
        }
        .header .logo img {
            max-height: 60px; /* Adjust as needed */
            width: auto;
            margin: 0 auto 15px auto; /* Center logo image */
        }
        .header .secondary-cta p {
            font-size: 1.1em;
            font-weight: 600;
            color: #555;
            margin: 0;
        }
        @media (min-width: 768px) { /* Side-by-side for larger screens */
            .header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                text-align: left;
            }
            .header .logo img {
                margin: 0;
            }
            .header .secondary-cta {
                text-align: right;
            }
        }


        /* --------------------------------------------------------------
            MAIN CONTENT STYLES
        ----------------------------------------------------------------- */
        .main-content .tagline {
            color: #555;
            font-style: italic;
            margin-top: 0;
        }
        .feature-image-container {
            margin: 20px 0;
        }
        .feature-image-container img {
            border-radius: 5px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .callout-box {
            background-color: #f0f4f8; /* Light blue-grey */
            padding: 20px;
            margin: 20px 0;
            border-radius: 5px;
            border-left: 5px solid #3f51b5; /* Accent border */
        }
        .callout-box h3 {
            margin-top: 0;
        }

        /* --------------------------------------------------------------
            PARDOT FORM STYLES - Overriding default and Bootstrap
        ----------------------------------------------------------------- */
        #pardot-form {
            margin: 0;
            padding: 0;
        }

        #pardot-form .form-field,
        #pardot-form .submit {
            margin-bottom: 20px;
            padding: 0;
        }

        #pardot-form label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            color: #333;
        }

        /* Ensure labels are always above */
        #pardot-form .field-label {
            float: none !important;
            width: 100% !important;
            text-align: left !important;
            padding-left: 0 !important; /* Override inline style from Pardot */
        }

        #pardot-form input[type="text"],
        #pardot-form input[type="email"],
        #pardot-form input[type="tel"],
        #pardot-form input[type="url"],
        #pardot-form input[type="date"],
        #pardot-form input[type="number"],
        #pardot-form textarea,
        #pardot-form select {
            width: 100% !important; /* Important to override Pardot inline styles */
            padding: 12px 15px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 1em;
            color: #333;
            background-color: #fff;
            font-family: 'Open Sans', sans-serif;
            -webkit-appearance: none; /* For better iOS styling */
            margin-left: 0 !important; /* Override Pardot label-left style */
        }
        
        #pardot-form textarea {
            min-height: 120px;
            resize: vertical;
        }

        /* Specific for checkbox/radio groups if Pardot wraps them */
        #pardot-form .pd-checkbox, #pardot-form .pd-radio {
            margin-bottom: 10px;
        }
        #pardot-form .pd-checkbox label, #pardot-form .pd-radio label {
            display: inline-block;
            font-weight: normal;
            margin-left: 5px;
            vertical-align: middle;
        }
        #pardot-form .pd-checkbox input[type="checkbox"],
        #pardot-form .pd-radio input[type="radio"] {
            width: auto !important; /* Don't make checkboxes full width */
            margin-right: 5px;
            vertical-align: middle;
        }

        #pardot-form .description {
            font-size: 0.9em;
            color: #666;
            margin-top: 5px;
            margin-left: 0 !important; /* Override Pardot label-left style */
        }

        #pardot-form .error { /* Error messages */
            color: #c0392b; /* Red for errors */
            font-size: 0.9em;
            margin-top: 5px;
            margin-left: 0 !important;
        }
        #pardot-form .error input, #pardot-form .error textarea, #pardot-form .error select {
            border-color: #c0392b;
        }

        #pardot-form .submit input[type="submit"] {
            background-color: #3f51b5; /* Primary accent color */
            color: #fff;
            border: none;
            padding: 12px 25px;
            font-size: 1.1em;
            font-weight: 600;
            font-family: 'Montserrat', sans-serif;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s ease;
            width: auto; /* Default width or set to 100% if preferred */
            display: inline-block;
        }
        #pardot-form .submit input[type="submit"]:hover {
            background-color: #303f9f; /* Darker shade on hover */
        }
        #pardot-form .submit {
            text-align: left; /* Or 'center' if you prefer centered button */
            margin-left: 0 !important; /* Override Pardot label-left style */
        }
        
        /* Remove unnecessary styles when label is above */
        #pardot-form.label-left .field-label,
        #pardot-form.label-left input.text,
        #pardot-form.label-left input.date,
        #pardot-form.label-left textarea,
        #pardot-form.label-left select,
        #pardot-form.label-left span.value,
        #pardot-form.label-left .description,
        #pardot-form.label-left .no-label > *,
        #pardot-form.label-left .submit,
        #pardot-form.label-left .error.no-label {
            width: 100% !important;
            margin-left: 0 !important;
            float: none !important;
        }


        /* --------------------------------------------------------------
            SOCIAL SHARE
        ----------------------------------------------------------------- */
        .social-sharing {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        .social-sharing strong {
            display: block;
            margin-bottom: 10px;
            font-family: 'Montserrat', sans-serif;
        }
        .social-list {
            padding-left: 0;
            list-style: none;
            margin: 0;
        }
        .social-list li {
            display: inline-block;
            margin: 0 5px;
        }
        .social-list img {
            width: 32px; /* Larger icons */
            height: 32px;
            border: 0;
            box-shadow: none;
            transition: opacity 0.2s ease;
        }
        .social-list a:hover img {
            opacity: 0.7;
        }
        .social-list a:hover {
            text-decoration: none;
        }

        /* --------------------------------------------------------------
            FOOTER
        ----------------------------------------------------------------- */
        .footer {
            background-color: #333;
            color: #ccc;
            padding: 30px;
            text-align: center;
            font-size: 0.9em;
        }
        .footer p {
            margin: 0;
        }
        .footer a {
            color: #fff;
            text-decoration: underline;
        }
        .footer a:hover {
            color: #3f51b5; /* Accent color on hover */
        }

        /* --------------------------------------------------------------
            UTILITIES (If needed)
        ----------------------------------------------------------------- */
        .text-center { text-align: center; }

        /* Styles for editable tables if used in Pardot regions */
        table.editable-table {
            table-layout: fixed;
            width: 100%;
            margin-bottom: 1em;
            border-collapse: collapse;
        }
        table.editable-table td, table.editable-table th {
            padding: 8px 10px;
            border: 1px solid #ddd;
            text-align: left;
        }
        table.editable-table th {
            background-color: #f5f5f5;
            font-weight: bold;
        }
        /* Responsive table: cells stack on small screens */
        @media (max-width: 767px) {
            table.editable-table, 
            table.editable-table thead, 
            table.editable-table tbody, 
            table.editable-table th, 
            table.editable-table td, 
            table.editable-table tr {
                display: block;
            }
            table.editable-table thead tr {
                position: absolute;
                top: -9999px;
                left: -9999px;
            }
            table.editable-table tr {
                border: 1px solid #ccc;
                margin-bottom: 10px;
            }
            table.editable-table td {
                border: none;
                border-bottom: 1px solid #eee;
                position: relative;
                padding-left: 50%; /* Space for data label */
            }
            table.editable-table td:before {
                position: absolute;
                top: 6px;
                left: 6px;
                width: 45%;
                padding-right: 10px;
                white-space: nowrap;
                font-weight: bold;
                /* Get data label from th:
                   You would need JS to inject data-label attribute to td from th
                   or manually add it if Pardot allows.
                   For CSS only, it's harder.
                   Example: td:nth-of-type(1):before { content: "Header 1"; }
                */
            }
        }

    </style>
</head>
<body>

<div class="page-wrapper">

    <header class="header">
        <div class="logo">
            <div class="editable">
                <img src="https://placehold.co/250x60?text=Your+Logo" alt="logo" pardot-region="logo" pardot-region-type="image">
            </div>
        </div>
        <div class="secondary-cta">
            <div class="editable">
                <p pardot-region="secondary-cta">
                    Speak with a Consultant <br>
                    1-888-555-1234
                </p>
            </div>
        </div>
    </header>

    <main>
        <section class="content-section main-content">
            <div class="editable">
                <h1 pardot-region="title">Business Trends Report</h1>
            </div>
            <div class="editable">
                <h2 class="tagline" pardot-region="subtitle">Everything you need to know this quarter.</h2>
            </div>
            <div class="editable" pardot-region="main-content">
                <p>New markets. New innovations. New policies. How will the next wave of globalization impact your organization? Today's business leaders want straightforward, clear and well-informed views on the dynamics currently reshaping the business climate. LenoxSoft's Business Trends Report features key trends related to pressing organizational questions. We show how these trends will continue to influence strategic priorities across platforms: management, employee engagement, and leadership. </p>
                <p>Download this report today to learn about:</p>
                <ul>
                    <li>Social impact</li>
                    <li>Globalization</li>
                    <li>The New C-Suite</li>
                    <li>Changing dynamics</li>
                    <li>Synergies</li>
                </ul>
            </div>
        </section>

        <section class="content-section feature-area">
            <div class="feature-image-container">
                <div class="editable">
                     <img src="https://placehold.co/740x400?text=Feature+Image" alt="placeholder image" pardot-region="feature_image" pardot-region-type="image">
                </div>
            </div>
            
            <div class="callout-box">
                <div class="editable" pardot-region="callout_box">
                    <h3>About the Author</h3>
                    <p>With three decades of experience, Deven Gupta, is a management consultant and partner at LenoxSoft. He has spent his career helping entrepreneurs and their management teams create value and grow their businesses. Gupta has developed a broad range of expertise in employee and workplace trends.</p>
                    <p><a href="#">Read more</a></p>
                </div>
            </div>
        </section>

        <section class="content-section form-area">
            <div class="editable">
                <h2 pardot-region="form_title">Get The Report</h2> <!-- Optional: Add a title for the form section -->
            </div>
            <div class="editable clearfix"> <!-- clearfix might not be needed with modern CSS -->
                %%content%% <!-- Pardot Form content injected here -->
            </div>
        </section>

        <section class="content-section social-sharing">
            <div class="editable" pardot-region="share_this">
                <strong>Share This:</strong>
                <ul id="share-buttons" class="social-list">
                    <li>
                        <a href="https://www.facebook.com/sharer.php?u=https://www.pardot.com" target="_blank">
                            <img src="https://simplesharebuttons.com/images/somacro/facebook.png" alt="Facebook" />
                        </a>
                    </li>
                    <li>
                        <a href="https://twitter.com/share?url=https://www.pardot.com&text=template-title" target="_blank">
                            <img src="https://simplesharebuttons.com/images/somacro/twitter.png" alt="Twitter" />
                        </a>
                    </li>
                    <li>
                        <a href="https://www.linkedin.com/shareArticle?mini=true&url=https://www.pardot.com" target="_blank">
                            <img src="https://simplesharebuttons.com/images/somacro/linkedin.png" alt="LinkedIn" />
                        </a>
                    </li>
                    <li>
                        <a href="mailto:?Subject=template-title&Body=I%20saw%20this%20and%20thought%20of%20you!%20 https://www.pardot.com">
                            <img src="https://simplesharebuttons.com/images/somacro/email.png" alt="Email" />
                        </a>
                    </li>
                </ul>
            </div>
        </section>
    </main>

    <footer class="footer">
        <div class="editable">
            <p pardot-region="footer-content" pardot-region-type="basic"><a href="#">Privacy Policy</a> | <a href="#">Company Name</a> © <script>document.write(new Date().getFullYear())</script></p>
        </div>
    </footer>

</div><!-- /.page-wrapper -->

<!-- Bootstrap JS can be removed if no Bootstrap JS components (like modals, dropdowns) are used -->
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js" integrity="sha384-aJ21OjlMXNL5UyIl/XNwTMqvzeRMZH2w8c5cRVpzpU8Y5bApTppSuUkhZXN0VxHd" crossorigin="anonymous"></script>
<script>
    $(document).ready(function() {
        /**
         * Replace placeholder URL and title in social sharing links.
         */
        function updateSocialLinks() {
            var pageUrl = window.location.href;
            var pageTitle = $("title").text();

            $(".social-list a").each(function() {
                var linkHref = $(this).attr('href');
                if (linkHref) {
                    linkHref = linkHref.replace(/https?:\/\/www\.pardot\.com/g, encodeURIComponent(pageUrl));
                    linkHref = linkHref.replace(/template-title/g, encodeURIComponent(pageTitle));
                    $(this).attr('href', linkHref);
                }
            });
        }
        updateSocialLinks();

        /**
         * Pardot label alignment fix.
         * The CSS aims to override this, but this can be a fallback
         * if Pardot injects styles that are hard to overcome.
         * However, with our CSS, #pardot-form .field-label should always be display:block.
         * This script adds 'label-left' if Pardot's default is float:left.
         * Our CSS then overrides .label-left styles to ensure single column.
         */
        if (typeof pardot !== 'undefined' && typeof pardot.$ === 'function') {
            pardot.$(function() {
                var $ = window.pardot.$; // Use Pardot's jQuery
                var $labelAlignment = $('#pardot-form .field-label').css('float');
                if ($labelAlignment == 'left') {
                    $('#pardot-form').addClass('label-left');
                }
                // Our CSS already handles making .label-left form elements stack,
                // so this mainly just ensures the class is there if Pardot expects it for other logic.
            });
        } else {
            // Fallback if pardot.$ is not available (e.g. testing outside Pardot)
            // This part might not be needed if you only test within Pardot.
            var $labelAlignmentGlobal = $('#pardot-form .field-label').css('float');
            if ($labelAlignmentGlobal == 'left') {
                $('#pardot-form').addClass('label-left');
            }
        }
    });
</script>
</body>
</html>
```