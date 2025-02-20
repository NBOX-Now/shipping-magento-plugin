document.addEventListener("DOMContentLoaded", (event) => {
  /**
   * Handle showing of login form
   */
  const showLogin = document.getElementById("showLogin");
  if (showLogin) {
    showLogin.addEventListener("click", (event) => {
      document.getElementById("login-wrap").classList.remove("hide");
      document.getElementById("login-control").classList.add("hide");
    });
  }

  /**
   * Handle showing of product list
   */
  const productView = document.getElementById("productView");
  if (productView) {
    productView.addEventListener("click", (event) => {
      if (event.target.dataset.toggle == "show") {
        document.getElementById("product-list").classList.remove("hide");
        event.target.textContent = "Hide products";
        event.target.dataset.toggle = "hide";
      } else {
        document.getElementById("product-list").classList.add("hide");
        event.target.textContent = "View products";
        event.target.dataset.toggle = "show";
      }
    });
  }
});
