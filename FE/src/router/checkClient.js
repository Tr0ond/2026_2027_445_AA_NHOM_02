import axios from "axios";
import apiUrl from "../utils/api";
import { createToaster } from "@meforma/vue-toaster";
const toaster = createToaster({ position: "top-right" });
export default function (to, from, next) {
    var token = localStorage.getItem("key_client");
    axios
        .get(apiUrl("client/check-token"), {
            headers: {
                Authorization: "Bearer " + token,
            },
        })
        .then((res) => {
            if (res.data.status) {
                localStorage.setItem("ho_va_ten", res.data.ho_ten);
                localStorage.setItem("email_kh", res.data.email);
                localStorage.setItem("check_kh", res.data.status);
                next();
            } else {
                toaster.error(res.data.message);
                next("/client/dang-nhap");
            }
        });
}