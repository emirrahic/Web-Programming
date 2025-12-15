$(function () {
  if (!$.spapp) {
    console.error('SPAPP plugin not found. Ensure jquery.spapp.js is loaded before custom.js.');
    return;
  }

  var app = $.spapp({
    defaultView: '#home',
    templateDir: './frontend/views/',
    pageNotFound: '#home'
  });


  app.run();


  function setActive(hash) {
    var h = hash && hash.length ? hash : '#home';
    $('.dropdown-item, .nav-link').removeClass('active');
    $('.dropdown-item[href="' + h + '"], .nav-link[href="' + h + '"]').addClass('active');
  }
  $(window).on('hashchange', function () { setActive(location.hash); });
  setActive(location.hash);


  // Logout handler
  $(document).on('click', '#logout-btn', function (e) {
    e.preventDefault();
    Auth.logout();
  });

  $(document).on('click', '.dropdown-item', function () {
    $('.navbar-collapse').collapse('hide');
  });
});