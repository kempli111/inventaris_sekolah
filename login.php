<?php
include "config.php";
session_start();

$error_message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (!empty($username) && !empty($password)) {
        $stmt = $conn->prepare("SELECT id, password, profile_picture, role FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $stmt->bind_result($id, $hashed_password, $profile_picture, $role);
            $stmt->fetch();

            if (password_verify($password, $hashed_password)) {
                $_SESSION['user_id'] = $id;
                $_SESSION['username'] = $username;
                $_SESSION['role'] = $role;
                $_SESSION['profile_picture'] = $profile_picture;

                if ($role == 'admin') {
                    header("Location: dashboard.php");
                    exit();
                } elseif ($role == 'superadmin') {
                    header("Location: superadmin.php");
                    exit();
                
                } elseif ($role == 'user') {
                    header("Location: duser.php");
                    exit();
                }
            } else {
                $error_message = "❌ Password salah!";
            }
        } else {
            $error_message = "❌ Username tidak ditemukan!";
        }
        $stmt->close();
    } else {
        $error_message = "❌ Harap isi username dan password!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Login System</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/png" href="images/icons/favicon.ico"/>
    <link rel="stylesheet" type="text/css" href="vendor/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="fonts/font-awesome-4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" type="text/css" href="fonts/Linearicons-Free-v1.0.0/icon-font.min.css">
    <link rel="stylesheet" type="text/css" href="vendor/animate/animate.css">
    <link rel="stylesheet" type="text/css" href="vendor/css-hamburgers/hamburgers.min.css">
    <link rel="stylesheet" type="text/css" href="vendor/animsition/css/animsition.min.css">
    <link rel="stylesheet" type="text/css" href="vendor/select2/select2.min.css">
    <link rel="stylesheet" type="text/css" href="vendor/daterangepicker/daterangepicker.css">
    <link rel="stylesheet" type="text/css" href="css/util.css">
    <link rel="stylesheet" type="text/css" href="css/main.css">
    <style>
        body {
            background-color: #ffffff !important;
        }
        .error-message {
            color: #ff4444;
            font-weight: bold;
            text-align: center;
            margin-top: 10px;
            padding: 10px;
            border-radius: 5px;
            background-color: #ffe5e5;
        }
        .container-login100 {
            background-color: #ffffff;
        }
        .wrap-login100 {
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .login100-form-title {
            color: #469ced;
            font-size: 28px;
            font-weight: 700;
        }
        .login100-form-btn {
            background-color: #469ced;
            transition: all 0.3s ease;
        }
        .login100-form-btn:hover {
            background-color: #3182ce;
        }
        .input100:focus + .focus-input100 + .label-input100 {
            color: #469ced;
        }
        .input100:focus + .focus-input100::after {
            background-color: #469ced;
        }
        .txt1 {
            color: #469ced;
        }
        .txt1:hover {
            color: #3182ce;
        }
        .login100-form-social-item {
            background-color: #469ced;
            transition: all 0.3s ease;
        }
        .login100-form-social-item:hover {
            background-color: #3182ce;
        }
        .label-checkbox100::before {
            border: 2px solid #469ced;
        }
        .input-checkbox100:checked + .label-checkbox100::before {
            background-color: #469ced;
        }
        
        /* Styling untuk input dan label */
        .wrap-input100 {
            position: relative;
            margin-bottom: 20px;
        }
        
        .input100 {
            width: 100%;
            padding: 15px;
            border: none;
            border-bottom: 2px solid #e6e6e6;
            outline: none;
            font-size: 16px;
            color: #333;
            background: transparent;
            transition: all 0.3s ease;
        }
        
        .label-input100 {
            position: absolute;
            top: 15px;
            left: 0;
            font-size: 16px;
            color: #999;
            pointer-events: none;
            transition: all 0.3s ease;
        }
        
        .input100:focus ~ .label-input100,
        .input100:not(:placeholder-shown) ~ .label-input100 {
            top: -10px;
            font-size: 13px;
            color: #469ced;
        }
        
        .input100:focus {
            border-bottom-color: #469ced;
        }
        
        /* Hapus placeholder bawaan */
        .input100::placeholder {
            color: transparent;
        }
    </style>
</head>
<body>
    
    <div class="limiter">
        <div class="container-login100">
            <div class="wrap-login100">
                <form class="login100-form validate-form" method="POST">
                    <span class="login100-form-title p-b-43">
                        Login to continue
                    </span>

                    <!-- Menampilkan pesan kesalahan -->
                    <?php if (!empty($error_message)): ?>
                        <div class="error-message"><?php echo $error_message; ?></div>
                    <?php endif; ?>
                    
                    <div class="wrap-input100 validate-input">
                        <input class="input100" type="text" name="username" placeholder=" ">
                        <span class="label-input100">Username</span>
                    </div>
                    
                    <div class="wrap-input100 validate-input">
                        <input class="input100" type="password" name="password" placeholder=" ">
                        <span class="label-input100">Password</span>
                    </div>

                    <div class="flex-sb-m w-full p-t-3 p-b-32">
                        <div class="contact100-form-checkbox">
                            <input class="input-checkbox100" id="ckb1" type="checkbox" name="remember-me">
                            <label class="label-checkbox100" for="ckb1">
                                Remember me
                            </label>
                        </div>

                        <div>
                            <a href="forgot_password.php" class="txt1">
                                Forgot Password?
                            </a>
                        </div>
                    </div>

                    <div class="container-login100-form-btn">
                        <button class="login100-form-btn">
                            Login
                        </button>
                    </div>
                    
                    <div class="text-center p-t-46 p-b-20">
                        <span class="txt2">
                            or sign up using
                        </span>
                    </div>

                    <div class="login100-form-social flex-c-m">
                        <a href="#" class="login100-form-social-item flex-c-m bg1 m-r-5">
                            <i class="fa fa-facebook-f" aria-hidden="true"></i>
                        </a>

                        <a href="#" class="login100-form-social-item flex-c-m bg2 m-r-5">
                            <i class="fa fa-twitter" aria-hidden="true"></i>
                        </a>
                    </div>
                </form>

                <div class="login100-more" style="background-image: url('images/bg-01.jpg');">
                </div>
            </div>
        </div>
    </div>

    <script src="vendor/jquery/jquery-3.2.1.min.js"></script>
    <script src="vendor/animsition/js/animsition.min.js"></script>
    <script src="vendor/bootstrap/js/popper.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.min.js"></script>
    <script src="vendor/select2/select2.min.js"></script>
    <script src="vendor/daterangepicker/moment.min.js"></script>
    <script src="vendor/daterangepicker/daterangepicker.js"></script>
    <script src="vendor/countdowntime/countdowntime.js"></script>
    <script src="js/login.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Tambahkan kelas has-val ke input yang sudah memiliki nilai
            const inputs = document.querySelectorAll('.input100');
            inputs.forEach(input => {
                input.addEventListener('input', function() {
                    if(this.value.trim() !== "") {
                        this.classList.add('has-val');
                    } else {
                        this.classList.remove('has-val');
                    }
                });
                
                // Check initial value
                if(input.value.trim() !== "") {
                    input.classList.add('has-val');
                }
            });
        });
    </script>

</body>
</html>
