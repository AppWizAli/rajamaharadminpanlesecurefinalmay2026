<!-- ✅ Spinner HTML -->
<div class="spinner-overlay" id="spinner">
  <div class="spinner">
    <div class="circle-spinner"></div>
  </div>
</div>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">


<div class="wrapper">

  <div class="body-overlay"></div>

  <!-------sidebar--design------------>

  <div id="sidebar">
    <div class="sidebar-header">
      <h3>
        <img src="img/logo.7d81eaa5dc8099bb3edf.jpg" class="img-fluid" alt="Urdu Bolo Image" />



        <span>Urdu Bolo</span>
      </h3>
      <hr style="border-color: black; border: 0.5px solid rgb(218, 208, 208);">
    </div>
    <ul class="list-unstyled component m-0">
      <li class="active">
        <a href="index.php" class="dashboard">
          <i class="dw dw-computer custom-icon-size custom-icon-light"></i>Dashboard
        </a>
      </li>

      <li class="dropdown">
        <a href="#submenu2" data-toggle="collapse" aria-expanded="false" class="dropdown-toggle">
          <i class="dw dw-video-camera-1 custom-icon-size custom-icon-light"></i>Drama
        </a>
        <ul class="collapse menu" id="submenu2">
          <li><a href="adddrama.php"><i class="dw dw-add custom-icon-size custom-icon-light"></i> Add Drama</a></li>
          <li><a href="product.php"><i class="dw dw-eye custom-icon-size custom-icon-light"></i> View Drama</a></li>
          <li><a href="trendingdrama.php"><i class="dw dw-eye custom-icon-size custom-icon-light"></i> Trending Drama</a></li>
        </ul>
      </li>

      <li class="dropdown">
        <a href="#submenu1" data-toggle="collapse" aria-expanded="false" class="dropdown-toggle">
          <i class="dw dw-video-camera custom-icon-size custom-icon-light"></i>Seasons
        </a>
        <ul class="collapse menu" id="submenu1">
          <li><a href="view_season.php"><i class="dw dw-tv custom-icon-size custom-icon-light"></i> View Seasons</a></li>
        </ul>
      </li>

      <li class="dropdown">
        <a href="#submenu5" data-toggle="collapse" aria-expanded="false" class="dropdown-toggle">
          <i class="dw dw-group custom-icon-size custom-icon-light"></i>Groups
        </a>
        <ul class="collapse menu" id="submenu5">
          <li><a href="show_groups.php"><i class="dw dw-list3 custom-icon-size custom-icon-light"></i> All Groups</a></li>
          <li><a href="create_dgroups.php"><i class="dw dw-add-user custom-icon-size custom-icon-light"></i> Add New Groups</a></li>
        </ul>
      </li>

      <li class="dropdown">
        <a href="#submenu3" data-toggle="collapse" aria-expanded="false" class="dropdown-toggle">
          <i class="dw dw-user-12 custom-icon-size custom-icon-light"></i>Users
        </a>
        <ul class="collapse menu" id="submenu3">
          <li><a href="usersrecords.php"><i class="dw dw-user custom-icon-size custom-icon-light"></i> All Users</a></li>
          <li><a href="user.php"><i class="dw dw-add-user custom-icon-size custom-icon-light"></i> Add User</a></li>
          <li><a href="users_dvideos.php"><i class="dw dw-video-camera-1 custom-icon-size custom-icon-light"></i> Assign Video To User</a></li>
        </ul>
      </li>

      <li class="dropdown">
        <a href="#submenu4" data-toggle="collapse" aria-expanded="false" class="dropdown-toggle">
          <i class="dw dw-user-13 custom-icon-size custom-icon-light"></i>Admin
        </a>
        <ul class="collapse menu" id="submenu4">
          <li><a href="admin_records.php"><i class="dw dw-user-2 custom-icon-size custom-icon-light"></i> All Admins</a></li>
          <li><a href="admin.php"><i class="dw dw-add-user custom-icon-size custom-icon-light"></i> Add New Admin</a></li>
        </ul>
      </li>

      <li class="dropdown">
        <a href="#submenu6" data-toggle="collapse" aria-expanded="false" class="dropdown-toggle">
          <i class="dw dw-notification custom-icon-size custom-icon-light"></i>Notifications
        </a>
        <ul class="collapse menu" id="submenu6">
          <li><a href="show_notifaction.php"><i class="dw dw-bell custom-icon-size custom-icon-light"></i> All Notifications</a></li>
          <li><a href="add_notifaction.php"><i class="dw dw-add custom-icon-size custom-icon-light"></i> Add New Notification</a></li>
        </ul>
      </li>

      <li class="dropdown">
        <a href="#submenu7" data-toggle="collapse" aria-expanded="false" class="dropdown-toggle">
          <i class="fas fa-ad custom-icon-size custom-icon-light"></i> Banners
        </a>
        <ul class="collapse menu" id="submenu7">
          <li><a href="addbanner.php"><i class="dw dw-add custom-icon-size custom-icon-light"></i> Add Banner</a></li>
          <li><a href="view_banner.php"><i class="dw dw-eye custom-icon-size custom-icon-light"></i> View Banners</a></li>
        </ul>
      </li>

      <li class="dropdown">
        <a href="#submenu8" data-toggle="collapse" aria-expanded="false" class="dropdown-toggle">
          <i class="fas fa-box custom-icon-size custom-icon-light"></i> Apk

        </a>
        <ul class="collapse menu" id="submenu8">
          <li><a href="upload_apk.php"><i class="dw dw-upload custom-icon-size custom-icon-light"></i> Upload Apk</a></li>
        </ul>
      </li>
    </ul>

  </div>



  <!-------sidebar--design- close----------->



  <!-------page-content start----------->

  <div id="content">

    <!------top-navbar-start----------->

    <div class="top-navbar shadow ">
      <div class="xd-topbar ">
        <div class="row">
          <div class="col-2 col-md-1 col-lg-1 order-2 order-md-1 align-self-center">
            <div class="xp-menubar">
              <span class="material-icons text-white">signal_cellular_alt</span>
            </div>
          </div>

          <div class="col-md-5 col-lg-3 order-3 order-md-2">
            <div class="xp-searchbar">
              <form>
                <div class="input-group">
                  <input type="search" class="form-control"
                    placeholder="Search">
                  <div class="input-group-append">
                    <button class="btn" type="submit" id="button-addon2">
                      <i class="fas fa-search"></i>
                    </button>
                  </div>

                </div>
              </form>
            </div>
          </div>


          <div class="col-10 col-md-6 col-lg-8 order-1 order-md-3">
            <div class="xp-profilebar text-right">
              <nav class="navbar p-0">
                <ul class="nav navbar-nav flex-row ml-auto">
                  <li class="dropdown nav-item active">
                    <a class="nav-link" href="#" data-toggle="dropdown">
                      <span class="material-icons">notifications</span>
                      <span class="notification">4</span>
                    </a>
                    <ul class="dropdown-menu">
                      <li><a href="#">You Have 4 New Messages</a></li>
                      <li><a href="#">You Have 4 New Messages</a></li>
                      <li><a href="#">You Have 4 New Messages</a></li>
                      <li><a href="#">You Have 4 New Messages</a></li>
                    </ul>
                  </li>

                  <li class="nav-item">
                    <a class="nav-link" href="#">
                      <span class="material-icons">question_answer</span>
                    </a>
                  </li>

                  <li class="dropdown nav-item">
                    <a class="nav-link" href="#" data-toggle="dropdown">
                      <img src="img/1.png" style="width:40px; border-radius:50%;" />
                      <span class="xp-user-live"></span>
                    </a>
                    <ul class="dropdown-menu small-menu">
                      <li><a href="#">
                          <span class="material-icons">person_outline</span>
                          Profile
                        </a></li>
                      <li><a href="#">
                          <span class="material-icons">settings</span>
                          Settings
                        </a></li>
                      <li><a href="logout.php">
                          <span class="material-icons">logout</span>
                          Logout
                        </a></li>

                    </ul>
                  </li>


                </ul>
              </nav>
            </div>
          </div>

        </div>

        <!-- <div class="xp-breadcrumbbar text-center">
				   <h4 class="page-title">Dashboard</h4>
				   <ol class="breadcrumb">
					 <li class="breadcrumb-item"><a href="#">Vishweb</a></li>
					 <li class="breadcrumb-item active" aria-curent="page">Dashboard</li>
				   </ol>
				</div> -->


      </div>
    </div>
    <!------top-navbar-end----------->


    <!------main-content-start----------->

    <div class="main-content">




    </div>

    <!-- ✅ Spinner CSS -->
    <style>
      /* Overlay with blur */
      .spinner-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100vw;
        height: 100vh;
        background: rgba(255, 255, 255, 0.2);
        backdrop-filter: blur(8px);
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 9999;
        display: none;
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
        background: rgb(86, 100, 115);
        box-shadow: 0 0 12px rgba(0, 123, 255, 0.6);
        animation: orbit 1.2s linear infinite;
      }

      .spinner::after {
        background: #6f42c1;
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
        border-top: 6px solid #007bff;
        border-radius: 50%;
        animation: spin 1s linear infinite, glow 1.8s ease-in-out infinite;
        box-shadow: 0 0 20px rgba(0, 123, 255, 0.4);
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

      /* Ring Rotation */
      @keyframes spin {
        0% {
          transform: rotate(0deg);
        }

        100% {
          transform: rotate(360deg);
        }
      }

      /* Glowing Ring Animation */
      @keyframes glow {

        0%,
        100% {
          box-shadow: 0 0 15px rgba(0, 123, 255, 0.4);
        }

        50% {
          box-shadow: 0 0 25px rgba(0, 123, 255, 0.8);
        }
      }
    </style>



    <script>
      document.querySelectorAll('a').forEach(link => {
        link.addEventListener('click', function(e) {
          const href = this.getAttribute('href');
          if (href && !href.startsWith('#')) {
            e.preventDefault(); // Prevent the default navigation

            // Show the spinner
            document.getElementById('spinner').style.display = 'flex';

            // Set a fixed delay for 2 seconds (this ensures the spinner stays for a consistent time)
            setTimeout(function() {
              // After 2 seconds, navigate to the link
              window.location.href = href;
            }, 1000); // 2 seconds fixed delay
          }
        });
      });
    </script>
    <script>
      document.querySelector('.xp-menubar').addEventListener('click', function() {
        document.body.classList.toggle('sidebar-closed');
      });
    </script>