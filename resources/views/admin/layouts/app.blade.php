<!DOCTYPE html>
<html
  lang="{{ config()->get('app.locale') }}"
  class="light-style layout-navbar-fixed layout-menu-fixed layout-compact"
  dir="{{ config()->get('app.locale') == 'ar' ? 'rtl' : 'ltr' }}"
  data-theme="theme-default"
  data-assets-path="{{ asset('admin/assets') }}/"
  data-template="vertical-menu-template-starter">
  <head>
    <meta charset="utf-8" />
    <meta
      name="viewport"
      content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />

    <title>MOTORS</title>

    <meta name="description" content="" />

    @include('admin.includes.styles')

  </head>

  <body>
    <!-- Layout wrapper -->
    <div class="layout-wrapper layout-content-navbar">
      <div class="layout-container">
        <!-- Menu -->

        @include('admin.includes.aside')

        <!-- / Menu -->

        <!-- Layout container -->
        <div class="layout-page">
          <!-- Navbar -->

            @include('admin.includes.header')

          <!-- / Navbar -->

          <!-- Content wrapper -->
          <div class="content-wrapper">
            <!-- Content -->

            <div class="container-xxl flex-grow-1 container-p-y">
                @yield('content')
            </div>
            <!-- / Content -->


            <div class="content-backdrop fade"></div>
          </div>
          <!-- Content wrapper -->
        </div>
        <!-- / Layout page -->
      </div>

      <!-- Overlay -->
      <div class="layout-overlay layout-menu-toggle"></div>

      <!-- Drag Target Area To SlideIn Menu On Small Screens -->
      <div class="drag-target"></div>
    </div>
    <!-- / Layout wrapper -->

    <form action="{{ route('admin.logout') }}" method="post" id="logout_form">
        @csrf
    </form>
    @include('admin.includes.scripts')

  </body>
</html>
