const loginForm = document.getElementById("loginForm");

const passwordInput = document.getElementById("password");
const togglePassword = document.getElementById("togglePassword");


// パスワード表示・非表示
togglePassword.addEventListener("click", function () {

    if (passwordInput.type === "password") {

        passwordInput.type = "text";
        togglePassword.textContent = "隠す";

    } else {

        passwordInput.type = "password";
        togglePassword.textContent = "表示";

    }

});


// ログインフォーム
loginForm.addEventListener("submit", function (event) {

    const userId = document.getElementById("userId").value;
    const password = document.getElementById("password").value;
    const errorMessage = document.getElementById("errorMessage");


    // 未入力チェック
    if (userId === "" || password === "") {

        event.preventDefault();

        errorMessage.textContent =
            "ユーザーIDとパスワードを入力してください。";

        return;
    }

    // 入力されていれば何もしない

});