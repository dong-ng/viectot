<?php 
$title = "Đặt lại mật khẩu";
$body['header'] = '
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,600,700">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
';
$body['footer'] = '

';
require_once __DIR__ . '/header.php';
?>
<head>
<style>
@import url("https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap");

body {
    font-family: "Poppins", sans-serif;
    background: linear-gradient(-45deg, #ee7752, #e73c7e, #23a6d5, #23d5ab);
    background-size: 400% 400%;
    animation: gradient 15s ease infinite;
    margin: 0;
    padding: 0;
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 100vh;
}

@keyframes gradient {
    0% { background-position: 0% 50%; }
    50% { background-position: 100% 50%; }
    100% { background-position: 0% 50%; }
}

.forgot-password-container {
    background-color: rgba(255, 255, 255, 0.9);
    border-radius: 20px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
    padding: 2rem;
    width: 90%;
    max-width: 400px;
    backdrop-filter: blur(10px);
    transition: all 0.3s ease;
}

@media (max-width: 480px) {
    .forgot-password-container {
        width: 95%;
        padding: 1.5rem;
    }
}

.forgot-password-container:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 40px rgba(0, 0, 0, 0.2);
}

h4 {
    font-size: 1.5rem;
    background: linear-gradient(45deg, #23a6d5, #23d5ab);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    text-align: center;
    padding: 5px 0 5px 0;
}

.form-group {
    margin-bottom: 1.25rem;
    position: relative;
}

.form-control {
    width: 100%;
    padding: 0.75rem 1rem 0.75rem 2.5rem;
    border: 2px solid #e0e0e0;
    border-radius: 10px;
    font-size: 1rem;
    transition: all 0.3s ease;
    box-sizing: border-box;
}

.form-control:focus {
    border-color: #23a6d5;
    box-shadow: 0 0 0 3px rgba(35, 166, 213, 0.1);
}

.form-group i {
    position: absolute;
    left: 1rem;
    top: 50%;
    transform: translateY(-50%);
    color: #999;
    transition: all 0.3s ease;
}

.form-control:focus + i {
    color: #23a6d5;
}

.btn-primary {
    width: 100%;
    padding: 0.75rem;
    background: linear-gradient(45deg, #23a6d5, #23d5ab);
    border: none;
    border-radius: 10px;
    color: white;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    font-size: 1rem;
}

.btn-primary:hover {
    background: linear-gradient(45deg, #23d5ab, #23a6d5);
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(35, 166, 213, 0.4);
}

.text-center {
    text-align: center;
}

.text-muted {
    color: #777;
    font-size: 0.9rem;
    margin-top: 1rem;
}

.text-muted a {
    color: #23a6d5;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.3s ease;
}

.text-muted a:hover {
    color: #e73c7e;
    text-decoration: underline;
}

.cf-turnstile {
    margin-bottom: 1.25rem;
    display: flex;
    justify-content: center;
}

hr {
    border: 0;
    height: 1px;
    background-image: linear-gradient(to right, rgba(0, 0, 0, 0), rgba(0, 0, 0, 0.75), rgba(0, 0, 0, 0));
    margin: 1.25rem 0;
}

@media (max-width: 480px) {
    h4 {
        font-size: 1.3rem;
    }
    .form-control {
        font-size: 0.9rem;
        padding: 0.6rem 1rem 0.6rem 2.2rem;
    }
    .btn-primary {
        font-size: 0.9rem;
        padding: 0.6rem;
    }
    .text-muted {
        font-size: 0.8rem;
    }
}

@media (min-width: 768px) {
    .forgot-password-container {
        padding: 3rem;
        max-width: 450px;
    }
    h4 {
        font-size: 2rem;
    }
    .form-control {
        font-size: 1.1rem;
        padding: 0.85rem 1rem 0.85rem 2.8rem;
    }
    .btn-primary {
        font-size: 1.1rem;
        padding: 0.85rem;
    }
    .text-muted {
        font-size: 1rem;
    }
}
</style>
</head>

<body>
    <!-- [ Pre-loader ] start -->
    <div class="loader-bg">
        <div class="loader-track">
            <div class="loader-fill"></div>
        </div>
    </div>
    <!-- [ Pre-loader ] End -->
<div class="forgot-password-container">
    <h4>KHÔI PHỤC MẬT KHẨU</h4>
    <form id="forgotPasswordForm">
        <div class="form-group">
            <input type="email" class="form-control" id="email" placeholder="Email">
            <i class="fas fa-envelope"></i>
        </div>
        <!--<div class="cf-turnstile" data-sitekey="0x4AAAAAAA8Zp6NpTO9D9aWf" data-theme="light" ></div>-->
        <?php if($_SERVER['HTTP_HOST'] == 'api.4gsieutoc.vn'):?>
                                   
                           <div class="cf-turnstile" data-sitekey="0x4AAAAAAA8gpzrUMVWu5wiz" data-theme="light"></div>
                       
                        <?php else :;?>
                               
                           <div class="cf-turnstile" data-sitekey="0x4AAAAAAA6O7bdhZ3ZavXQi" data-theme="light"></div>
                        
                        <?php endif;?>

        <button type="submit" class="btn btn-primary" id="btnForgot" >Xác Thực</button>
    </form>
    <hr>
    <p class="text-center text-muted">
        Đã nhớ mật khẩu? <a href="<?= BASE_URL('') ?>home/login">Đăng Nhập</a>
    </p>
    <p class="text-center text-muted">
        Chưa có tài khoản? <a href="<?= BASE_URL('') ?>home/register">Đăng Ký</a>
    </p>
</div>

<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async></script>

<script type="text/javascript">
    const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true,
        didOpen: (toast) => {
            toast.addEventListener('mouseenter', Swal.stopTimer)
            toast.addEventListener('mouseleave', Swal.resumeTimer)
        }
    })

    document.getElementById('forgotPasswordForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const btnForgot = document.getElementById('btnForgot');
        btnForgot.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Đang xử lý...';
        btnForgot.disabled = true;
        
        const token = document.querySelector('[name="cf-turnstile-response"]').value;

        fetch('<?= BASE_URL('') ?>ajaxs/client/resetPassword.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: "forgotPassword",
                email: document.getElementById('email').value,
                captcha: token
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.status == 'success') {
                Toast.fire({
                    icon: 'success',
                    title: data.msg
                });
                setTimeout(() => {
                    window.location.href = '<?= BASE_URL('') ?>client/reset-password';
                }, 3000);
            } else {
                Toast.fire({
                    icon: 'error',
                    title: data.msg
                });
            }
            btnForgot.innerHTML = 'XÁC THỰC';
            btnForgot.disabled = false;
        })
        .catch(error => {
            Toast.fire({
                icon: 'error',
                title: 'Không thể xử lý'
            });
            btnForgot.innerHTML = 'XÁC THỰC';
            btnForgot.disabled = false;
        });
    });
    
</script>