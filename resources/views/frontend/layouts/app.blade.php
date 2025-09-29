<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{systemflag('appName')}} – Global eSIM for Travelers</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <!-- AOS animations -->
    <link rel="stylesheet" href="https://unpkg.com/aos@2.3.1/dist/aos.css" />
    <!-- Custom CSS -->
    <link rel="stylesheet" href="{{asset('frontend/styles.css')}}">
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="{{asset(systemflag('favicon'))}}">
</head>

<body>
    @include('frontend.partials.navbar')
    <main>
        @yield('frontent-content')
        @include('frontend.partials.footer')
    </main>
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- AOS JS -->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init({
            once: true
        });
        // Change navbar background when scrolling
        window.addEventListener('scroll', function() {
            const nav = document.querySelector('.navbar');
            if (window.scrollY > 50) {
                nav.classList.add('scrolled');
            } else {
                nav.classList.remove('scrolled');
            }
        });

        // Dark/Light Mode Toggle
        const themeToggle = document.getElementById('theme-toggle');
        const themeIcon = document.getElementById('theme-icon');
        const body = document.body;

        // Check for saved theme preference or default to light mode
        const savedTheme = localStorage.getItem('theme') || 'light';
        
        // Apply saved theme
        if (savedTheme === 'dark') {
            body.setAttribute('data-bs-theme', 'dark');
            themeIcon.className = 'bi bi-moon-fill';
        } else {
            body.setAttribute('data-bs-theme', 'light');
            themeIcon.className = 'bi bi-sun-fill';
        }

        // Theme toggle event listener
        if (themeToggle) {
            themeToggle.addEventListener('click', function() {
                const currentTheme = body.getAttribute('data-bs-theme');
                
                if (currentTheme === 'dark') {
                    body.setAttribute('data-bs-theme', 'light');
                    themeIcon.className = 'bi bi-sun-fill';
                    localStorage.setItem('theme', 'light');
                } else {
                    body.setAttribute('data-bs-theme', 'dark');
                    themeIcon.className = 'bi bi-moon-fill';
                    localStorage.setItem('theme', 'dark');
                }
            });
        }
    </script>
</body>

</html>
