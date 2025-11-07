function fn_ValForm() {
    var sMsg = "";

    var nameVal = document.getElementById("name").value;
    var emailVal = document.getElementById("email").value;
    var msgVal = document.getElementById("message").value;

    // Validasi kosong
    if (nameVal === "") {
        sMsg += "\n* Anda belum mengisikan nama";
    }
    if (emailVal === "") {
        sMsg += "\n* Anda belum mengisikan email";
    }
    if (msgVal === "") {
        sMsg += "\n* Anda belum mengisikan pesan";
    }

    // Validasi email
    // ^[a-z0-9][a-z0-9._\-]{0,}[a-z0-9]@[a-z0-9][a-z0-9\.\-]{0,}[a-z0-9]\.[a-z0-9]{2,4}$
    var emailRe = /^[a-z0-9][a-z0-9._\-]{0,}[a-z0-9]@[a-z0-9][a-z0-9.\-]{0,}[a-z0-9]\.[a-z0-9]{2,4}$/i;
    if (emailVal && !emailRe.test(emailVal)) {
        sMsg += "\n* Format email tidak valid";
    }

    if (sMsg !== "") {
        alert("Peringatan:\n" + sMsg);
        return false;
    } else {
        return true;
    }
}
