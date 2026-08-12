import { createRouter, createWebHistory } from "vue-router"; // cài vue-router: npm install vue-router@next --save
import checkAdmin from "./checkAdmin";
import checkClient from "./checkClient";

const routes = [
    // ============= Client ==================
    {
        path: "/",
        component: () => import("../components/Client/TrangChu/index.vue"),
        meta: { layout: "client" },
    },
    {
        path: "/client/dang-nhap",
        component: () => import("../components/Client/DangNhap/index.vue"),
        meta: { layout: "blank" },
    },
    {
        path: "/client/dang-ky",
        component: () => import("../components/Client/DangKy/index.vue"),
        meta: { layout: "blank" },
    },
    {
        path: "/client/quen-mat-khau",
        component: () => import("../components/Client/QuenMatKhau/index.vue"),
        meta: { layout: "blank" },
    },
    {
        path: "/client/lay-lai-mat-khau",
        component: () => import("../components/Client/LayLaiMatKhau/index.vue"),
        meta: { layout: "blank" },
    },
    {
        path: "/client/phim/dang-chieu",
        component: () => import("../components/Client/Phim/DangChieu/index.vue"),
        meta: { layout: "client" },

    },
    {
        path: "/client/phim/sap-chieu",
        component: () => import("../components/Client/Phim/SapChieu/index.vue"),
        meta: { layout: "client" },
    },
    {
        path: "/chi-tiet-bai-viet/:id_bai_viet",
        component: () => import("../components/Client/ChiTietBaiViet/index.vue"),
        meta: { layout: "client" }
    },
    {
        path: "/chi-tiet-phim/:id_phim",
        component: () => import("../components/Client/ChiTietPhim/index.vue"),
        meta: { layout: "client" }
    },
    {
        path: "/client/dat-ve/:id_suat_chieu",
        component: () => import("../components/Client/DatVe/index.vue"),
        meta: { layout: "client" },
        beforeEnter: checkClient,
    },
    {
        path: "/client/thanh-toan/:ma_don_hang",
        component: () => import("../components/Client/ThanhToan/index.vue"),
        meta: { layout: "client" },
        beforeEnter: checkClient,
        props: true,
    },
    // {
    //     path: "/client/chi-tiet-don-hang/:ma_don_hang",
    //     component: () => import("../components/Client/ChiTietDonHang/index.vue"),
    //     meta: { layout: "client" },
    //     beforeEnter: checkClient,
    //     props: true,
    // },
    {
        path: "/client/bai-viet",
        component: () => import("../components/Client/BaiViet/index.vue"),
        meta: { layout: "client" },
    },
    {
        path: "/client/about",
        component: () => import("../components/Client/About/index.vue"),
        meta: { layout: "client" },
    },
    {
        path: "/client/profile",
        component: () => import("../components/Client/Profile/index.vue"),
        meta: { layout: "client" },
        beforeEnter: checkClient,
    },
    {
    path: '/client/thanh-toan/cho-xac-nhan/:ma_don_hang',
    component: () => import('../components/Client/XacNhanThanhToan/index.vue'),
    meta: { layout: 'client' },
    },

    // ==================== VIEW ====================
    // {
    //     path: "/",
    //     component: () => import("../components/Test/index.vue"),
    // },

    // ============= Admin ==================
    {
        path: "/admin/phong-chieu",
        component: () => import("../components/Admin/PhongChieu/index.vue"),
        beforeEnter: checkAdmin,
    },
    {
        path: "/admin/dich-vu",
        component: () => import("../components/Admin/DichVu/index.vue"),
        beforeEnter: checkAdmin,
    },
    {
        path: "/admin/nhan-vien",
        component: () => import("../components/Admin/NhanVien/index.vue"),
        beforeEnter: checkAdmin,
    },
    {
        path: "/admin/phan-quyen",
        component: () => import("../components/Admin/PhanQuyen/index.vue"),
        beforeEnter: checkAdmin,
    },
    {
        path: "/admin/khach-hang",
        component: () => import("../components/Admin/KhachHang/index.vue"),
        beforeEnter: checkAdmin,
    },
    {
        path: "/admin/phim",
        component: () => import("../components/Admin/Phim/index.vue"),
        beforeEnter: checkAdmin,
    },
    {
        path: "/admin/ve",
        component: () => import("../components/Admin/Ve/index.vue"),
        beforeEnter: checkAdmin,
    },
    {
        path: "/admin/suat-chieu",
        component: () => import("../components/Admin/SuatChieu/index.vue"),
        beforeEnter: checkAdmin,
    },
    {
        path: "/admin/ghe",
        component: () => import("../components/Admin/Ghe/index.vue"),
        beforeEnter: checkAdmin,
    },
    {
        path: "/admin/bai-viet",
        component: () => import("../components/Admin/BaiViet/index.vue"),
        beforeEnter: checkAdmin,
    },
    {
        path: "/admin/voucher",
        component: () => import("../components/Admin/Voucher/index.vue"),
        beforeEnter: checkAdmin,
    },
    {
        path: "/admin/dang-nhap",
        component: () => import("../components/Admin/DangNhap/index.vue"),
        meta: { layout: "blank" },
    },
    {
        path: "/admin/don-hang",
        component: () => import("../components/Admin/DonHang/index.vue"),
        beforeEnter: checkAdmin,
    },
    {
        path: "/admin/binh-luan",
        component: () => import("../components/Admin/BinhLuan/index.vue"),
        beforeEnter: checkAdmin,
    },
    {
        path: "/admin/danh-gia",
        component: () => import("../components/Admin/DanhGia/index.vue"),
        beforeEnter: checkAdmin,
    },
    {
        path: "/admin/dashboard",
        component: () => import("../components/Admin/Dashboard/index.vue"),
        beforeEnter: checkAdmin,
    },
    //Thống kê
    {
        path: "/admin/thong-ke/khach-hang-chi-tieu",
        component: () => import("../components/Admin/ThongKe/TKChiTieuKhachHang/index.vue"),
        beforeEnter: checkAdmin,
    },
    {
        path: "/admin/thong-ke/don-hang",
        component: () => import("../components/Admin/ThongKe/TKDonHangTheoNgay/index.vue"),
        beforeEnter: checkAdmin,
    },
    {
        path: "/admin/thong-ke/ve",
        component: () => import("../components/Admin/ThongKe/TKVe/index.vue"),
        beforeEnter: checkAdmin,
    },
    {
        path: "/admin/thong-ke/khach-hang-dang-ky",
        component: () => import("../components/Admin/ThongKe/TKKhachHangDangKy/index.vue"),
        beforeEnter: checkAdmin,
    },
    {
        path: "/admin/thong-ke/phim",
        component: () => import("../components/Admin/ThongKe/TKPhim/index.vue"),
        beforeEnter: checkAdmin,
    },
    {
        path: "/admin/thong-ke/suat-chieu",
        component: () => import("../components/Admin/ThongKe/TKSuatChieu/index.vue"),
        beforeEnter: checkAdmin,
    },
    {
        path: "/admin/thong-ke/dich-vu",
        component: () => import("../components/Admin/ThongKe/TKDichVu/index.vue"),
        beforeEnter: checkAdmin,
    },
    {
        path: "/admin/thong-ke/voucher",
        component: () => import("../components/Admin/ThongKe/TKVoucher/index.vue"),
        beforeEnter: checkAdmin,
    },
    {
        path: "/admin/profile",
        component: () => import("../components/Admin/Profile/index.vue"),
        beforeEnter: checkAdmin,
    },
    {
        path: "/admin/in-ve/:ma_hoa_don",
        name: "admin-in-ve",
        component: () => import("../components/Admin/InVe/index.vue"),
        
        meta: { layout: "blank" },
        
    },
    {
        path: "/admin/quet-ve",
        component: () => import("../components/Admin/QuetVe/index.vue"),
        beforeEnter: checkAdmin,
    },
];

const router = createRouter({
    history: createWebHistory(),
    routes: routes,
});

export default router;
