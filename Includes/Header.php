<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Skinova - Personalized Skincare</title>

    <!-- Bootstrap 5 -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <!-- Bootstrap Icons -->
    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
    >

    <!-- Skinova CSS -->
    <link
        rel="stylesheet"
        href="/Skinova/Assets/css/style.css"
    >

</head>


<body>


<!-- ================= NAVBAR ================= -->

<nav class="navbar navbar-expand-lg bg-white sticky-top shadow-sm">

    <div class="container">


        <!-- LOGO -->

        <a
            class="navbar-brand skinova-logo"
            href="/Skinova/index.php"
        >
            Skinova
        </a>


        <!-- MOBILE MENU BUTTON -->

        <button
            class="navbar-toggler"
            type="button"
            data-bs-toggle="collapse"
            data-bs-target="#mainNavbar"
            aria-controls="mainNavbar"
            aria-expanded="false"
            aria-label="Toggle navigation"
        >

            <span class="navbar-toggler-icon"></span>

        </button>


        <!-- NAVIGATION -->

        <div
            class="collapse navbar-collapse"
            id="mainNavbar"
        >


            <!-- MENU -->

            <ul class="navbar-nav mx-auto mb-3 mb-lg-0">

                <li class="nav-item">

                    <a
                        class="nav-link"
                        href="/Skinova/index.php"
                    >
                        Home
                    </a>

                </li>


                <li class="nav-item">

                    <a
                        class="nav-link"
                        href="/Skinova/Products/Products.php"
                    >
                        Products
                    </a>

                </li>


                <li class="nav-item">

                    <a
                        class="nav-link"
                        href="/Skinova/Ai/Consult.php"
                    >
                        AI Consultation
                    </a>

                </li>


                <li class="nav-item">

                    <a
                        class="nav-link"
                        href="/Skinova/Products/Products.php"
                    >
                        Categories
                    </a>

                </li>

            </ul>


            <!-- RIGHT SIDE -->

            <div class="navbar-actions">


                <!-- Search -->

                <a
                    href="/Skinova/Products/Search.php"
                    class="nav-icon"
                    title="Search"
                >

                    <i class="bi bi-search"></i>

                </a>


                <!-- Wishlist -->

                <a
                    href="/Skinova/Wishlist/Wishlist.php"
                    class="nav-icon"
                    title="Wishlist"
                >

                    <i class="bi bi-heart"></i>

                </a>


                <!-- Cart -->

                <a
                    href="/Skinova/Cart/Cart.php"
                    class="nav-icon"
                    title="Cart"
                >

                    <i class="bi bi-cart3"></i>

                </a>


                <!-- Login -->

                <a
                    href="/Skinova/Auth/Login.php"
                    class="btn btn-dark login-button"
                >
                    Login
                </a>


            </div>

        </div>

    </div>

</nav>