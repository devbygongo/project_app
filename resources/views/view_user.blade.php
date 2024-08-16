<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View User</title>
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
</head>
<body>
    <div class="container">
        <h2>User Information</h2>
        <div id="user-info"></div>
        <button id="logout-button" class="btn btn-danger">Logout</button>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            let token = localStorage.getItem('auth_token');

            if (token) {
                fetch("{{ route('view_user.api') }}", {
                    method: 'GET',
                    headers: {
                        'Authorization': 'Bearer ' + token,
                        'Content-Type': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.data && data.data.length > 0) {
                        let user = data.data[0]; // Access the first user in the array

                        let userInfo = `<p>Name: ${user.name}</p>
                                        <p>Email: ${user.email}</p>
                                        <p>Mobile: ${user.mobile}</p>
                                        <p>Role: ${user.role}</p>
                                        <p>Address: ${user.address_line_1}</p>`;
                        document.getElementById('user-info').innerHTML = userInfo;
                    } else {
                        document.getElementById('user-info').innerHTML = '<p>No user data found.</p>';
                    }
                })
                .catch(error => console.error('Error:', error));
            } else {
                alert('No token found, please log in first.');
                window.location.href = "{{ route('login.view') }}";
            }

            // Logout button click event
            document.getElementById('logout-button').addEventListener('click', function() {
                fetch("{{ route('logout') }}", {
                    method: 'GET',
                    headers: {
                        'Authorization': 'Bearer ' + token,
                        'Content-Type': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    // Clear the token from localStorage
                    localStorage.removeItem('auth_token');
                    // Redirect to login page
                    window.location.href = "{{ route('login.view') }}";
                })
                .catch(error => console.error('Error:', error));
            });
        });
    </script>
</body>
</html>