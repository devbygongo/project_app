<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
</head>
<body>
    <div class="container">
        <h2>Login</h2>
        @if(session('error'))
            <div class="alert alert-danger">
                {{ session('error') }}
            </div>
        @endif
        <form id="loginForm">
            @csrf
            <div class="form-group">
                <label for="email">Email Address:</label>
                <input type="email" name="email" id="email" class="form-control">
            </div>
            <div class="form-group">
                <label for="mobile">Mobile Number:</label>
                <input type="text" name="mobile" id="mobile" class="form-control">
            </div>
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" name="password" id="password" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary">Login</button>
        </form>
    </div>

    <script>
        document.getElementById('loginForm').addEventListener('submit', function(event) {
            event.preventDefault();

            let email = document.getElementById('email').value;
            let mobile = document.getElementById('mobile').value;
            let password = document.getElementById('password').value;
            let tokenInput = document.querySelector('input[name="_token"]').value;

            fetch("{{ route('login') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': tokenInput
                },
                body: JSON.stringify({
                    email: email,
                    mobile: mobile,
                    password: password
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.data.token) {
                    // Store token in localStorage
                    localStorage.setItem('auth_token', data.token);

                    // Redirect to the view_user page
                    alert('Login successful.');
                    window.location.href = "{{ route('view_user.page') }}";
                } else {
                    alert('Login failed: ' + (data.message || 'Unknown error.'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred: ' + error.message);
            });
        });
    </script>
</body>
</html>
