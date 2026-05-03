<!DOCTYPE html>
<html>
<head>
	<!-- Basic Page Info -->
	<meta charset="utf-8">
	<title>DeskApp - Bootstrap Admin Dashboard HTML Template</title>

	<!-- Site favicon -->
	<link rel="apple-touch-icon" sizes="180x180" href="vendors/images/apple-touch-icon.png">
	<link rel="icon" type="image/png" sizes="32x32" href="vendors/images/favicon-32x32.png">
	<link rel="icon" type="image/png" sizes="16x16" href="vendors/images/favicon-16x16.png">

	<!-- Mobile Specific Metas -->
	<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">

	<!-- Google Font -->
	<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
	<!-- CSS -->
	<link rel="stylesheet" type="text/css" href="vendors/styles/core.css">
	<link rel="stylesheet" type="text/css" href="vendors/styles/icon-font.min.css">
	<link rel="stylesheet" type="text/css" href="vendors/styles/style.css">
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="vendors/styles/mycss/login.css">
	<!-- Global site tag (gtag.js) - Google Analytics -->
	<script async src="https://www.googletagmanager.com/gtag/js?id=UA-119386393-1"></script>
	<script>
		window.dataLayer = window.dataLayer || [];
		function gtag(){dataLayer.push(arguments);}
		gtag('js', new Date());

		gtag('config', 'UA-119386393-1');
	</script>
    <style>
        /* Spinner Overlay */
        .spinner-overlay {
          position: fixed;
          top: 0;
          left: 0;
          width: 100vw;
          height: 100vh;
          background: rgba(255, 255, 255, 0.2); /* Light blur */
          backdrop-filter: blur(8px); /* Strong blur effect */
          display: none; /* Hidden by default */
          justify-content: center;
          align-items: center;
          z-index: 9999;
        }
        
        /* Spinner Container */
        .spinner {
          position: relative;
          width: 80px;
          height: 80px;
        }
        
        /* Orbit Balls */
        .spinner::before,
        .spinner::after {
          content: "";
          position: absolute;
          top: 50%;
          left: 50%;
          width: 18px;
          height: 18px;
          margin: -9px 0 0 -9px;
          border-radius: 50%;
          background: rgb(86, 100, 115); /* Base color */
          box-shadow: 0 0 12px rgba(0, 123, 255, 0.6); /* Soft glow */
          animation: orbit 1.2s linear infinite;
        }
        
        .spinner::after {
          background: #6f42c1; /* Purple ball */
          animation-delay: 0.6s;
        }
        
        /* Circular Spinner Ring */
        .circle-spinner {
          position: absolute;
          top: 0;
          left: 0;
          width: 100%;
          height: 100%;
          border: 6px solid rgba(0, 123, 255, 0.2);
          border-top: 6px solid #007bff; /* Blue top */
          border-radius: 50%;
          animation: spin 1s linear infinite, glow 1.8s ease-in-out infinite;
          box-shadow: 0 0 20px rgba(0, 123, 255, 0.4); /* Additional ring glow */
        }
        
        /* Orbit Animation */
        @keyframes orbit {
          0% {
            transform: rotate(0deg) translateX(30px) rotate(0deg);
          }
          100% {
            transform: rotate(360deg) translateX(30px) rotate(-360deg);
          }
        }
        
        /* Spinner Ring Rotation */
        @keyframes spin {
          0% { transform: rotate(0deg); }
          100% { transform: rotate(360deg); }
        }
        
        /* Glowing Ring Pulse */
        @keyframes glow {
          0%, 100% {
            box-shadow: 0 0 15px rgba(0, 123, 255, 0.4);
          }
          50% {
            box-shadow: 0 0 25px rgba(0, 123, 255, 0.8);
          }
        }
        </style>
        
</head>
<body class="login-page">
    <!-- ✅ Spinner HTML -->
<div class="spinner-overlay" id="spinner">
    <div class="spinner">
      <div class="circle-spinner"></div>
    </div>
  </div>
  
	<div class="login-header box-shadow" style="background-color:#0B132B;">
		<div class="container-fluid d-flex justify-content-between align-items-center">
			<div class="brand-logo">
				<a href="login.php">
				<img src="src/images/Urdu Bolo Logo Png.png" alt="" class="light-logo">
				</a>
			</div>
		</div>
	</div>
	<div class="login-wrap d-flex align-items-center flex-wrap justify-content-center">
	<div class="container py-5">
    <div class="row align-items-center">
        <!-- Image Section -->
        <div class="col-md-6 col-lg-7 mb-4 mb-md-0">
            <img src="vendors/images/login-page-img.png" alt="Login Visual" class="img-fluid rounded-3 shadow-lg">
        </div>

        <!-- Login Form -->
        <div class="col-md-6 col-lg-5">
            <div class="login-box bg-white shadow-lg rounded-4 p-5">
                <div class="login-title mb-4 text-center">
                    <h2 class="text-dark fw-bold">Login to <span class="text-primary">Admin Panel</span></h2>
                    <p class="text-muted small">Please enter your credentials to continue</p>
                </div>
                <form action="login_process.php" method="post">
                    <!-- Username -->
                    <div class="form-group mb-2 position-relative">
                        <label class="form-label fw-semibold text-dark">Username</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light icon-border " >
                                <i class="fas fa-user  fs-6" style="font-size:17px;"></i>
                            </span>
                            <input type="text" class="form-control form-control-lg border-primary" placeholder="Enter username" name="username" required>
                        </div>
                    </div>

                    <!-- Password -->
                    <div class="form-group mb-2 position-relative">
                        <label class="form-label fw-semibold text-dark">Password</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light fs-6 icon-border">
                                <i class="fas fa-lock  fs-6 " style="font-size:17px;"></i>
                            </span>
                            <input type="password" class="form-control form-control-lg border-primary" placeholder="Enter password" name="password" required>
                        </div>
                    </div>

                 <!-- Submit Button -->
<div class="d-flex justify-content-end">
  <button type="submit" class="btn btn-primary btn-lg text-uppercase fw-bold shadow-sm">
    <i class="fas fa-sign-in-alt me-2"></i> Log In
  </button>
</div>

                </form>
            </div>
        </div>
    </div>
</div>

	</div>
	<!-- js -->
	<script src="vendors/scripts/core.js"></script>
	<script src="vendors/scripts/script.min.js"></script>
	<script src="vendors/scripts/process.js"></script>
	<script src="vendors/scripts/layout-settings.js"></script>
    <script>
        document.querySelector('form').addEventListener('submit', function (e) {
          e.preventDefault(); // Prevent immediate submission
        
          // Show spinner
          document.getElementById('spinner').style.display = 'flex';
        
          // Wait 3 seconds, then submit the form
          setTimeout(() => {
            e.target.submit(); // Submit the form normally after delay
          }, 1000);
        });
        </script>
        
        
</body>
</html>