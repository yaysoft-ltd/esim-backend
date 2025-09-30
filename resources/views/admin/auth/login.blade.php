<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - {{systemflag('appName')}}|Admin</title>
    <!-- Bootstrap 5 CSS CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" xintegrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <!-- Google Fonts - Inter for a clean look -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            /* Subtle radial gradient background */
            background: radial-gradient(circle at top left, #a7e0ff 0%, transparent 40%),
                radial-gradient(circle at bottom right, #ffb3b3 0%, transparent 40%),
                #e0f2fe;
            /* light blue background */
            background-attachment: fixed;
            min-height: 100vh;
            /* Ensure full viewport height */
            display: flex;
            /* For centering content */
            align-items: center;
            /* For centering content */
            justify-content: center;
            /* For centering content */
            padding: 1rem;
        }

        /* Custom styles for a more attractive button and card */
        .card-glow-bs {
            box-shadow: 0 0.625rem 0.9375rem -0.1875rem rgba(0, 0, 0, 0.1), 0 0.25rem 0.375rem -0.125rem rgba(0, 0, 0, 0.05);
            /* Equivalent of shadow-xl */
            transition: all 0.3s ease-in-out;
            border-radius: 1rem !important;
            /* Force rounded-2xl equivalent */
            border: 1px solid #e2e8f0;
            /* border-gray-200 equivalent */
        }

        .card-glow-bs:hover {
            box-shadow: 0 1.25rem 1.5625rem -0.3125rem rgba(0, 0, 0, 0.15), 0 0.625rem 0.625rem -0.3125rem rgba(0, 0, 0, 0.08);
            /* Stronger shadow on hover */
            transform: translateY(-0.125rem);
            /* Slight lift */
        }

        .btn-gradient-bs {
            background: linear-gradient(to right, #4F46E5, #6366F1);
            /* Indigo to lighter indigo */
            border: none;
            /* Remove default button border */
            transition: all 0.3s ease;
            padding: 0.875rem 1.5rem;
            /* Equivalent to py-3.5 px-6 */
            border-radius: 0.75rem;
            /* Equivalent to rounded-xl */
            font-weight: 700;
            /* Equivalent to font-bold */
        }

        .btn-gradient-bs:hover {
            background: linear-gradient(to right, #6366F1, #4F46E5);
            /* Reverse gradient on hover */
            transform: translateY(-0.0625rem);
            /* Slight lift */
            box-shadow: 0 0.25rem 0.375rem rgba(0, 0, 0, 0.1);
        }

        /* Custom placeholder color for consistency across browsers */
        input::placeholder {
            color: #9ca3af;
            /* Equivalent to placeholder-gray-400 */
            opacity: 1;
            /* Firefox default is lower opacity */
        }
    </style>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" rel="stylesheet" />

</head>

<body>
    <div class="bg-white p-5 card-glow-bs" style="max-width: 480px; width: 100%;">
        <h2 class="display-5 fw-bolder text-center text-dark mb-4 lh-sm">Welcome Back!</h2>
        <p class="text-center text-muted mb-4">Sign in to your account</p>

        <form method="POST" id="loginForm">
            @csrf
            <!-- Email Address -->
            <div class="mb-4">
                <label for="email" class="form-label text-dark fs-6 fw-semibold mb-2">Email Address</label>
                <input
                    id="email"
                    type="email"
                    name="email"
                    value="{{ old('email') }}"
                    required
                    autofocus
                    class="form-control form-control-lg {{ $errors->has('email') ? 'is-invalid' : '' }}"
                    placeholder="Enter your email">
                @if ($errors->has('email'))
                <div class="invalid-feedback">{{ $errors->first('email') }}</div>
                @endif
            </div>

            <!-- Password -->
            <div class="mb-5">
                <label for="password" class="form-label text-dark fs-6 fw-semibold mb-2">Password</label>
                <input
                    id="password"
                    type="password"
                    name="password"
                    required
                    autocomplete="current-password"
                    class="form-control form-control-lg {{ $errors->has('password') ? 'is-invalid' : '' }}"
                    placeholder="Enter your password">
                @if ($errors->has('password'))
                <div class="invalid-feedback">{{ $errors->first('password') }}</div>
                @endif
            </div>

            <!-- Submit Button -->
            <div class="d-grid gap-2 mt-4">
                <button type="submit" class="btn btn-lg btn-gradient-bs text-white">
                    Log In Securely
                </button>
            </div>
        </form>
    </div>

    <!-- Bootstrap 5 JavaScript Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" xintegrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9Gkcapp53d4c_g7B2Q5jT2eP" crossorigin="anonymous"></script>

    <script src="{{ asset('assets/js/core/jquery-3.7.1.min.js') }}"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <script>
        toastr.options = {
            "positionClass": "toast-top-right",
            "progressBar": true,
            "closeButton": true,
        };
        $('#loginForm').on('submit', function(e) {
            e.preventDefault();

            let formData = new FormData(this);

            // Reset any previous errors
            $('.form-control').removeClass('is-invalid');

            $.ajax({
                url: "{{ route('login') }}",
                method: 'POST',
                data: formData,
                contentType: false,
                processData: false,

                success: function(response) {
                    toastr.success(response.message || 'Login successful');

                    setTimeout(() => {
                        window.location.href = "{{route('admin.dashboard')}}";
                    }, 1500);
                },

                error: function(xhr) {
                    if (xhr.status === 422) {
                        // Laravel validation errors
                        const errors = xhr.responseJSON.errors;
                        for (let field in errors) {
                            toastr.error(errors[field][0]);
                        }
                    } else if (xhr.status === 401) {
                        // Auth failed
                        toastr.error(xhr.responseJSON?.message || 'Invalid credentials.');
                    } else {
                        toastr.error('Something went wrong. Please try again.');
                    }
                }
            });
        });
    </script>
</body>

</html>
