<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Portfolio API Documentation</title>
    <link rel="stylesheet" type="text/css" href="https://unpkg.com/swagger-ui-dist@5.11.0/swagger-ui.css" />
    <link rel="icon" type="image/png" href="https://unpkg.com/swagger-ui-dist@5.11.0/favicon-32x32.png" />
    <style>
        html {
            box-sizing: border-box;
            overflow: -moz-scrollbars-vertical;
            overflow-y: scroll;
        }

        *,
        *:before,
        *:after {
            box-sizing: inherit;
        }

        body {
            margin: 0;
            background: #fafafa;
        }

        .topbar {
            background-color: #1f2937 !important;
        }

        /* Custom Dark Header */
        .swagger-ui .topbar .download-url-wrapper .select-label select {
            min-width: 200px;
        }
    </style>
</head>

<body>
    <div id="swagger-ui"></div>

    <script src="https://unpkg.com/swagger-ui-dist@5.11.0/swagger-ui-bundle.js" crossorigin></script>
    <script src="https://unpkg.com/swagger-ui-dist@5.11.0/swagger-ui-standalone-preset.js" crossorigin></script>

    <script>
        window.onload = function() {
            // Konfigurasi Swagger UI untuk memuat banyak file JSON
            const ui = SwaggerUIBundle({
                urls: [{
                        url: "{{ asset('docs/admin-api.json') }}",
                        name: "1. Admin Auth"
                    },
                    {
                        url: "{{ asset('docs/about-api.json') }}",
                        name: "2. Profile & About Me"
                    },
                    {
                        url: "{{ asset('docs/project-api.json') }}",
                        name: "3. Projects"
                    },
                    {
                        url: "{{ asset('docs/skill-api.json') }}",
                        name: "4. Skills"
                    },
                    {
                        url: "{{ asset('docs/experience-api.json') }}",
                        name: "5. Experiences"
                    },
                    {
                        url: "{{ asset('docs/certificate-api.json') }}",
                        name: "6. Certificates"
                    },
                    {
                        url: "{{ asset('docs/contact-api.json') }}",
                        name: "7. Contacts"
                    },
                    {
                        url: "{{ asset('docs/artwork-api.json') }}",
                        name: "Artwork API"
                    },
                    {
                        url: "{{ asset('docs/visitor-api.json') }}",
                        name: "9. Visitors"
                    },
                ],
                dom_id: '#swagger-ui',
                deepLinking: true,
                presets: [
                    SwaggerUIBundle.presets.apis,
                    SwaggerUIStandalonePreset
                ],
                plugins: [
                    SwaggerUIBundle.plugins.DownloadUrl
                ],
                layout: "StandaloneLayout",
                filter: true, // Fitur search endpoint
            });
            window.ui = ui;
        };
    </script>
</body>

</html>
