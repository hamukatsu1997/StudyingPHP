document.addEventListener("DOMContentLoaded", function () {

    fetch("../../PHP/User/user.php")
        .then(response => response.json())
        .then(data => {

            // ログインしていない
            if (!data.login) {

                window.location.href =
                    "../Login/login.html";

                return;
            }


            // ユーザー名を表示
            document.getElementById("userName").textContent =
                data.userName;

            document.getElementById("welcomeName").textContent =
                data.userName;

        })
        .catch(error => {

            console.error(
                "ユーザー情報の取得に失敗しました。",
                error
            );

        });

});