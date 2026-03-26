        <?php $asideLayoutClass = (isset($currentPage) && $currentPage === 'fixed_sidebar') ? ' menu_fixed' : ''; ?>
        <aside class="col-md-3 left_col<?php echo $asideLayoutClass; ?>" aria-label="Sidebar navigation">
          <div class="left_col scroll-view">
            <div class="navbar nav_title border-0">
              <a href="index.php?page=dashboard" class="site_title"><img src="assets/images/logo.svg" alt="Gentelella Alela!" class="logo-full logo-main" loading="lazy"><img src="assets/images/logo-icon.svg" alt="Gentelella" class="logo-icon" loading="lazy"></a>
            </div>

            <div class="clearfix"></div>

            <!-- menu profile quick info -->
            <div class="profile clearfix">
              <div class="profile_pic">
                <img src="assets/images/img.jpg" alt="User profile photo" class="img-circle profile_img" loading="lazy">
              </div>
              <div class="profile_info">
                <span>Welcome,</span>
                <h4>John Doe</h4>
              </div>
            </div>
            <!-- /menu profile quick info -->

            <br />

            <!-- sidebar menu -->
            <div id="sidebar-menu" class="main_menu_side hidden-print main_menu">
              <div class="menu_section">
                <ul class="nav side-menu">
                  <li><a><i class="bi bi-house"></i> Home <span class="bi bi-chevron-down"></span></a>
                    <ul class="nav child_menu">
                      <li><a href="index.php?page=dashboard">Dashboard 1</a></li>
                    </ul>
                  </li>
                  <li><a><i class="bi bi-pencil-square"></i> Forms <span class="bi bi-chevron-down"></span></a>
                    <ul class="nav child_menu">
                      <li><a href="index.php?page=form">General Form</a></li>
                      <li><a href="index.php?page=form_advanced">Advanced Components</a></li>
                      <li><a href="index.php?page=form_validation">Form Validation</a></li>
                      <li><a href="index.php?page=form_wizards">Form Wizard</a></li>
                      <li><a href="index.php?page=form_upload">Form Upload</a></li>
                      <li><a href="index.php?page=form_buttons">Form Buttons</a></li>
                    </ul>
                  </li>
                  <li><a><i class="bi bi-display"></i> UI Elements <span class="bi bi-chevron-down"></span></a>
                    <ul class="nav child_menu">
                      <li><a href="index.php?page=general_elements">General Elements</a></li>
                      <li><a href="index.php?page=media_gallery">Media Gallery</a></li>
                      <li><a href="index.php?page=typography">Typography</a></li>
                      <li><a href="index.php?page=icons">Icons</a></li>
                      <li><a href="index.php?page=widgets">Widgets</a></li>
                      <li><a href="index.php?page=invoice">Invoice</a></li>
                      <li><a href="index.php?page=inbox">Inbox</a></li>
                      <li><a href="index.php?page=calendar">Calendar</a></li>
                    </ul>
                  </li>
                  <li><a><i class="bi bi-table"></i> Tables <span class="bi bi-chevron-down"></span></a>
                    <ul class="nav child_menu">
                      <li><a href="index.php?page=tables">Tables</a></li>
                      <li><a href="index.php?page=tables_dynamic">Table Dynamic</a></li>
                    </ul>
                  </li>
                  <li><a><i class="bi bi-bar-chart"></i> Data Presentation <span class="bi bi-chevron-down"></span></a>
                    <ul class="nav child_menu">
                      <li><a href="index.php?page=chartjs">Chart.js</a></li>
                      <li><a href="index.php?page=echarts">ECharts</a></li>
                      <li><a href="index.php?page=other_charts">Other Charts</a></li>
                    </ul>
                  </li>
                </ul>
              </div>
            </div>
            <!-- /sidebar menu -->

            <!-- /menu footer buttons -->
            <div class="sidebar-footer hidden-small">
              <a data-bs-toggle="tooltip" data-bs-placement="top" title="Settings">
                <span class="fas fa-cog" aria-hidden="true"></span>
              </a>
              <a data-bs-toggle="tooltip" data-bs-placement="top" title="FullScreen">
                <span class="fas fa-expand" aria-hidden="true"></span>
              </a>
              <a data-bs-toggle="tooltip" data-bs-placement="top" title="Lock">
                <span class="fas fa-eye-slash" aria-hidden="true"></span>
              </a>
              <a data-bs-toggle="tooltip" data-bs-placement="top" title="Logout" href="login.html">
                <span class="fas fa-power-off" aria-hidden="true"></span>
              </a>
            </div>
            <!-- /menu footer buttons -->
          </div>
        </aside>