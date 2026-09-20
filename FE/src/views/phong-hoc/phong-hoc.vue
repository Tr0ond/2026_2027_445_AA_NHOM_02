<template>
  <div class="h-screen flex flex-col bg-slate-900 text-white overflow-hidden">
    <!-- Thanh trên -->
    <div class="h-14 shrink-0 border-b border-slate-700/50 bg-slate-900/90 backdrop-blur z-40">
      <div class="h-full px-4 sm:px-6 flex items-center justify-between gap-3">
        <div class="min-w-0">
          <span class="font-bold truncate inline-flex items-center gap-2">
            <span class="w-8 h-8 rounded-lg bg-brand-600 flex items-center justify-center text-sm"><i class="fa-solid fa-graduation-cap"></i></span>
            {{ phong.ten_lop }}
          </span>
          <span class="text-slate-400 ml-2 text-sm hidden sm:inline">{{ phong.mon_hoc }} · {{ phong.ngay_hoc }} {{ phong.gio_bat_dau }}</span>
          <span v-if="phong.trang_thai === 'dang_dien_ra'" class="nhan bg-rose-500/20 text-rose-400 ml-2">
            <span class="w-1.5 h-1.5 bg-rose-500 rounded-full animate-pulse"></span>Đang diễn ra
          </span>
        </div>
        <div class="flex items-center gap-2">
          <div class="hidden sm:flex items-center gap-2 px-3 py-1.5 bg-slate-800 rounded-lg border border-slate-700"><span class="w-2 h-2 bg-red-500 rounded-full animate-pulse"></span><span class="text-slate-300 text-xs font-mono">{{ thoiLuongPhongHienThi }}</span></div>
          <div class="hidden sm:flex items-center gap-1.5 px-2.5 py-1 bg-red-500/20 border border-red-500/30 rounded-lg text-red-400 text-xs font-medium"><i class="fa-solid fa-circle text-[8px] animate-pulse"></i>REC</div>
          <button v-if="laGiangVien" class="nut bg-rose-600 text-white hover:bg-rose-700 text-sm" @click="ketThucPhong">
            <i class="fa-solid fa-phone-slash"></i><span class="hidden sm:inline">Kết thúc buổi học</span>
          </button>
          <button v-else class="flex items-center gap-2 px-3 sm:px-4 py-2 rounded-xl text-sm font-semibold bg-slate-700 hover:bg-rose-600 text-slate-200 hover:text-white" @click="roiPhong"><i class="fa-solid fa-phone-slash"></i><span class="hidden sm:inline">Rời phòng</span></button>
        </div>
      </div>
    </div>

    <div class="flex flex-1 overflow-hidden min-h-0">
        <!-- Cột trái: video kiểu Zoom + QR overlay -->
        <div class="flex-1 min-w-0 flex flex-col p-3 gap-3 overflow-y-auto">
          <div ref="vungVideo" class="relative bg-black rounded-2xl overflow-hidden flex-1 min-h-[360px]">
            <!-- Công cụ bố cục -->
            <div class="absolute top-3 right-3 z-[25] flex rounded-xl overflow-hidden border border-white/10">
              <button class="px-3 py-1.5 text-xs font-semibold transition"
                :class="cheDoXem === 'loi' ? 'bg-amber-400 text-slate-900' : 'bg-slate-800/90 text-white hover:bg-slate-700'"
                @click="cheDoXem = 'loi'" title="Chế độ người đang nói (như Zoom)">
                <i class="fa-solid fa-square-poll-vertical mr-1"></i>Người nói
              </button>
              <button class="px-3 py-1.5 text-xs font-semibold transition"
                :class="cheDoXem === 'luoi' ? 'bg-amber-400 text-slate-900' : 'bg-slate-800/90 text-white hover:bg-slate-700'"
                @click="cheDoXem = 'luoi'" title="Xem lưới tất cả thành viên">
                <i class="fa-solid fa-table-cells mr-1"></i>Lưới
              </button>
              <button class="px-3 py-1.5 text-xs bg-slate-800/90 text-white hover:bg-slate-700" @click="toanManHinh" title="Toàn màn hình">
                <i class="fa-solid fa-expand"></i>
              </button>
            </div>

            <!-- Chưa cấu hình Agora -->
            <div v-if="!thongTinAgora?.app_id"
              class="h-full flex flex-col items-center justify-center text-center text-slate-400 p-6">
              <i class="fa-solid fa-video-slash text-5xl mb-4"></i>
              <div class="font-semibold text-amber-400 mb-2">Video Agora chưa được cấu hình</div>
              <div class="text-sm">{{ thongTinAgora?.canh_bao || 'Thiếu AGORA_APP_ID trong BE/.env' }}</div>
              <div class="text-sm mt-2 text-slate-500">Lấy App ID miễn phí tại console.agora.io → dán vào BE/.env → khởi động lại backend</div>
            </div>

            <!-- Các ô video -->
            <div v-else class="khung-o h-full" :class="cheDoXem === 'loi' ? 'che-do-loi' : 'che-do-luoi'">
              <div
                v-for="tv in danhSachSapXep"
                :key="tv.uid"
                :id="'o-video-' + tv.uid"
                class="o-video"
                :class="{
                  'o-video-lon': cheDoXem === 'loi' && tv.uid === uidChinh,
                  'dang-noi': tv.uid === dangNoiUid,
                  'o-toi': tv.laToi,
                }"
              >
                <!-- Lớp media: video Agora phát vào đây; avatar hiện khi không có video -->
                <div class="o-media">
                  <div v-if="!coVideo(tv)" class="o-avatar flex flex-col items-center justify-center h-full w-full">
                    <div class="vong-avatar">{{ chuVietHoa(tv.hoTen) }}</div>
                    <div class="text-xs text-slate-400 mt-1.5 text-center px-1">{{ tv.hoTen }}</div>
                  </div>
                </div>

                <!-- Huy hiệu đang chia sẻ -->
                <div v-if="tv.dangChiaSe" class="nhan bg-amber-400 text-slate-900 !absolute top-1.5 left-1.5" style="z-index: 5">
                  <i class="fa-solid fa-display"></i>Đang chia sẻ
                </div>

                <!-- Ghim / bỏ ghim -->
                <button v-if="!tv.laToi" class="nut-ghim" :class="{ 'dang-ghim': ghimUid === tv.uid }"
                  :title="ghimUid === tv.uid ? 'Bỏ ghim' : 'Ghim lên khung lớn'" @click="ghim(tv.uid)">
                  <i class="fa-solid fa-thumbtack"></i>
                </button>

                <!-- Tên + mic -->
                <div class="o-nhan">
                  <i v-if="!micCua(tv)" class="fa-solid fa-microphone-slash text-rose-500 mr-1"></i>
                  <i v-else class="fa-solid fa-microphone mr-1" :class="tv.uid === dangNoiUid ? 'text-emerald-400' : ''"></i>
                  {{ tv.hoTen }}<span v-if="tv.laToi" class="text-sky-400"> (bạn)</span>
                </div>
                <div v-if="tv.gioTay" class="absolute top-1.5 right-1.5 text-amber-400 text-base" style="z-index: 5">
                  <i class="fa-solid fa-hand"></i>
                </div>
              </div>
            </div>

            <!-- Overlay QR điểm danh -->
            <div v-if="hienQr" class="qr-overlay flex-col text-center p-6">
              <h5 class="text-amber-400 font-bold mb-1"><i class="fa-solid fa-qrcode mr-2"></i>Điểm danh bằng QR</h5>
              <p class="text-sm text-slate-400 mb-4">Dùng điện thoại quét mã bên dưới để điểm danh</p>
              <img v-if="qrDataUrl" :src="qrDataUrl" alt="Mã QR điểm danh" width="220" height="220" class="bg-white rounded-2xl p-2" />
              <div class="text-4xl font-extrabold mt-4 font-mono" :class="conLaiGiay <= 30 ? 'text-rose-500' : 'text-white'">
                {{ demNguoc }}
              </div>
              <div v-if="daDiemDanhToi" class="mt-3">
                <span class="nhan bg-emerald-500 text-white text-base !px-4 !py-1.5">
                  <i class="fa-solid fa-check"></i>Đã điểm danh thành công
                </span>
              </div>
            </div>
            <div v-if="!laGiangVien && daXinPhepVang" class="absolute inset-0 z-20 flex items-center justify-center bg-slate-950/80 backdrop-blur-sm p-6">
              <div class="max-w-sm text-center"><span class="w-20 h-20 mx-auto rounded-3xl bg-sky-500/20 border border-sky-500/30 text-sky-400 flex items-center justify-center text-3xl"><i class="fa-solid fa-file-circle-check"></i></span><h3 class="text-xl font-bold text-white mt-4">Đã xin phép vắng</h3><p class="text-sm text-slate-400 mt-2">Đơn của bạn đã được giảng viên duyệt. Bạn không cần quét mã QR điểm danh cho buổi học này.</p></div>
            </div>

            <!-- Chrome/Safari mobile có thể chặn âm thanh từ xa cho tới khi người dùng chạm. -->
            <button v-if="amThanhBiChan" type="button"
              class="absolute left-1/2 top-16 z-30 -translate-x-1/2 min-h-12 whitespace-nowrap rounded-xl bg-amber-400 px-4 py-3 font-bold text-slate-950 shadow-xl hover:bg-amber-300"
              @click="batLaiAmThanh">
              <i class="fa-solid fa-volume-high mr-2"></i>Chạm để bật âm thanh lớp học
            </button>
          </div>

          <!-- Thanh điều khiển media -->
          <div class="flex flex-wrap justify-center gap-2 sm:gap-3 shrink-0">
            <button class="w-14 sm:w-16 h-14 rounded-2xl flex flex-col items-center justify-center gap-1 text-xs disabled:cursor-not-allowed disabled:opacity-50" :class="micBat ? 'bg-slate-700 text-white hover:bg-slate-600' : 'bg-rose-600 text-white hover:bg-rose-700'"
              :disabled="!agoraClient" @click="batTatMic"
              :title="!agoraClient ? 'Đang kết nối phòng video' : (!quyenToi.mac && !laGiangVien ? 'Chưa được giáo viên cấp quyền dùng mic' : (!micTrack ? 'Nhấn để cấp quyền micro' : 'Bật/tắt micro'))">
              <i :class="micBat ? 'fa-solid fa-microphone' : 'fa-solid fa-microphone-slash'"></i>
              <span class="hidden sm:block">{{ micBat ? 'Mic' : 'Tắt' }}</span>
              <i v-if="!quyenToi.mac && !laGiangVien" class="fa-solid fa-lock text-[10px] opacity-70"></i>
            </button>

            <button class="w-14 sm:w-16 h-14 rounded-2xl flex flex-col items-center justify-center gap-1 text-xs disabled:cursor-not-allowed disabled:opacity-50" :class="camBat ? 'bg-slate-700 text-white hover:bg-slate-600' : 'bg-rose-600 text-white hover:bg-rose-700'"
              :disabled="!agoraClient" @click="batTatCam" :title="!camTrack ? 'Nhấn để cấp quyền camera' : 'Bật/tắt camera'">
              <i :class="camBat ? 'fa-solid fa-video' : 'fa-solid fa-video-slash'"></i>
              <span class="hidden sm:block">{{ camBat ? 'Camera' : 'Tắt' }}</span>
            </button>

            <button class="w-14 sm:w-16 h-14 rounded-2xl bg-slate-700 text-slate-200 hover:bg-slate-600 flex flex-col items-center justify-center gap-1 text-xs disabled:cursor-not-allowed disabled:opacity-50"
              :disabled="!agoraClient" @click="batLaiAmThanh" title="Khởi động lại âm thanh từ các thành viên">
              <i class="fa-solid fa-volume-high"></i>
              <span class="hidden sm:block">Âm thanh</span>
            </button>

            <button class="w-14 sm:w-16 h-14 rounded-2xl flex flex-col items-center justify-center gap-1 text-xs" :class="dangChiaSe ? 'bg-brand-600 text-white hover:bg-brand-700' : 'bg-slate-700 text-slate-200 hover:bg-slate-600'"
              :disabled="!agoraClient || !quyenToi.chia_se" @click="batTatChiaSe"
              :title="!quyenToi.chia_se ? 'Chưa được giáo viên cấp quyền chia sẻ màn hình' : 'Chia sẻ màn hình'">
              <i class="fa-solid fa-display"></i><span class="hidden sm:block">{{ dangChiaSe ? 'Dừng' : 'Chia sẻ' }}</span>
              <i v-if="!quyenToi.chia_se && !laGiangVien" class="fa-solid fa-lock text-[10px] opacity-70"></i>
            </button>

            <button v-if="!laGiangVien" class="w-14 sm:w-16 h-14 rounded-2xl flex flex-col items-center justify-center gap-1 text-xs"
              :class="quyenToi.gio_tay ? 'bg-amber-400 text-slate-900 hover:bg-amber-300' : 'bg-slate-800 text-amber-400 hover:bg-slate-700'"
              @click="gioTay">
              <i class="fa-solid fa-hand text-lg"></i><span class="hidden sm:block">Giơ tay</span>
            </button>
            <button v-if="laGiangVien" class="w-14 sm:w-16 h-14 rounded-2xl flex flex-col items-center justify-center gap-1 text-xs bg-teal-600 hover:bg-teal-700 text-white" aria-label="Mở bảng điểm danh" @click="moBangPhong('diem_danh')"><i class="fa-solid fa-qrcode text-lg" aria-hidden="true"></i><span class="hidden sm:block">QR</span></button>
            <span class="hidden sm:block w-px h-8 bg-slate-700 self-center"></span>
            <button class="w-14 sm:w-16 h-14 rounded-2xl flex flex-col items-center justify-center gap-1 text-xs bg-slate-700 hover:bg-slate-600 text-white" aria-label="Mở trò chuyện" @click="moBangPhong('chat')"><i class="fa-solid fa-comment text-lg" aria-hidden="true"></i><span class="hidden sm:block">Chat</span></button>
            <button class="w-14 sm:w-16 h-14 rounded-2xl flex flex-col items-center justify-center gap-1 text-xs bg-slate-700 hover:bg-slate-600 text-white" aria-label="Xem thành viên" @click="moBangPhong('thanh_vien')"><i class="fa-solid fa-users text-lg" aria-hidden="true"></i><span class="hidden sm:block">Thành viên</span></button>
            <button class="w-14 sm:w-16 h-14 rounded-2xl flex flex-col items-center justify-center gap-1 text-xs bg-slate-700 hover:bg-slate-600 text-white" @click="toanManHinh"><i class="fa-solid fa-expand text-lg"></i><span class="hidden sm:block">Phóng to</span></button>
          </div>

          <!-- Bảng điều khiển giảng viên: US14, US15, US16 -->
          <div v-if="false && laGiangVien" class="rounded-2xl bg-slate-900 border border-slate-800 p-4">
            <div class="flex flex-wrap items-center gap-2 mb-4">
              <button class="nut bg-amber-400 text-slate-900 hover:bg-amber-300 text-sm" @click="moPhienDiemDanh" :disabled="phienHienTai !== null">
                <i class="fa-solid fa-qrcode"></i>Mở phiên điểm danh QR
              </button>
              <select v-model="soPhut" class="o-nhap !w-auto !py-1.5 bg-slate-800 border-slate-700 text-white">
                <option :value="3">3 phút</option>
                <option :value="5">5 phút</option>
                <option :value="10">10 phút</option>
              </select>
              <button v-if="phienHienTai" class="nut bg-rose-600/90 text-white hover:bg-rose-600 text-sm" @click="dongPhien">
                Đóng phiên & đánh dấu vắng
              </button>
              <span v-if="phienHienTai" class="text-sm text-sky-400 font-mono">
                Phiên {{ phienHienTai.ma_phien }} · còn {{ demNguocGv }}
              </span>
            </div>

            <h6 class="font-bold text-sm mb-2 flex items-center gap-2">
              <i class="fa-solid fa-list-check text-brand-400"></i>Danh sách điểm danh
              <span class="nhan bg-emerald-500/15 text-emerald-400">Có mặt: {{ soCoMat }}</span>
              <span class="nhan bg-slate-700 text-slate-300">Tổng: {{ danhSachDiemDanh.length }}</span>
            </h6>
            <div class="overflow-auto max-h-64 rounded-xl border border-slate-800">
              <table class="w-full text-sm">
                <thead class="bg-slate-800/80 sticky top-0">
                  <tr class="text-left text-xs uppercase text-slate-400">
                    <th class="px-3 py-2.5">Mã SV</th><th class="px-3 py-2.5">Họ tên</th>
                    <th class="px-3 py-2.5">Trạng thái</th><th class="px-3 py-2.5">Giờ quét</th>
                    <th class="px-3 py-2.5 text-right">Thao tác</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-if="!danhSachDiemDanh.length">
                    <td colspan="5" class="px-3 py-8 text-center text-slate-500">Chưa mở phiên điểm danh nào.</td>
                  </tr>
                  <tr v-for="sv in danhSachDiemDanh" :key="sv.ma_sinh_vien" class="border-t border-slate-800">
                    <td class="px-3 py-2 font-mono text-xs">{{ sv.ma_sv_text }}</td>
                    <td class="px-3 py-2">{{ sv.ho_ten }}</td>
                    <td class="px-3 py-2"><span class="nhan" :class="mauBadge(sv.trang_thai_diem_danh)">{{ tenTrangThai(sv.trang_thai_diem_danh) }}</span></td>
                    <td class="px-3 py-2 text-xs text-slate-400">{{ sv.thoi_gian_diem_danh || '—' }}</td>
                    <td class="px-3 py-2">
                      <div v-if="phienHienTai" class="flex justify-end gap-1.5">
                        <button v-if="sv.trang_thai_diem_danh === 'chua_diem_danh'"
                          class="nut bg-emerald-500/15 text-emerald-400 hover:bg-emerald-500/25 text-xs !px-2.5 !py-1"
                          @click="diemDanhThuCong(sv)">Thủ công</button>
                        <select class="o-nhap !w-auto !py-1 !px-2 text-xs bg-slate-800 border-slate-700 text-white"
                          @change="suaTrangThai(sv, $event.target.value)">
                          <option value="" selected disabled>Sửa</option>
                          <option value="co_mat">Có mặt</option>
                          <option value="di_muon">Đi muộn</option>
                          <option value="vang">Vắng</option>
                          <option value="vang_co_phep">Vắng có phép</option>
                          <option value="chua_diem_danh">Xóa bản ghi</option>
                        </select>
                      </div>
                      <span v-else class="text-slate-600">—</span>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- Lớp nền cho bảng chức năng trên điện thoại -->
        <button v-if="bangMobileMo" type="button" class="fixed inset-0 z-40 bg-slate-950/70 backdrop-blur-sm lg:hidden" aria-label="Đóng bảng chức năng" @click="dongBangPhong"></button>

        <!-- Cột phải trên desktop, bottom sheet trên điện thoại -->
        <div
          class="fixed inset-x-0 bottom-0 z-50 max-h-[78dvh] flex-col rounded-t-2xl bg-slate-800 border-t border-slate-700 shadow-2xl lg:static lg:z-auto lg:flex lg:max-h-none lg:w-72 xl:w-80 lg:shrink-0 lg:rounded-none lg:border-t-0 lg:border-l lg:shadow-none"
          :class="bangMobileMo ? 'flex' : 'hidden lg:flex'"
          :style="bangMobileMo ? { paddingBottom: 'env(safe-area-inset-bottom)' } : undefined"
          :role="bangMobileMo ? 'dialog' : 'complementary'"
          :aria-modal="bangMobileMo ? 'true' : undefined"
          aria-label="Bảng chức năng lớp học"
        >
          <div class="flex min-h-12 items-center justify-between border-b border-slate-700/50 px-4 lg:hidden">
            <span class="text-sm font-semibold text-slate-100">Chức năng lớp học</span>
            <button type="button" class="flex h-11 w-11 items-center justify-center rounded-xl text-slate-300 hover:bg-slate-700 hover:text-white" aria-label="Đóng bảng chức năng" @click="dongBangPhong"><i class="fa-solid fa-xmark" aria-hidden="true"></i></button>
          </div>
          <div class="flex border-b border-slate-700/50 shrink-0"><button v-for="tab in cacTabPhong" :key="tab.k" class="flex-1 flex flex-col items-center gap-0.5 py-3 text-xs font-medium transition-colors" :class="tabPhong === tab.k ? 'text-indigo-400 border-b-2 border-indigo-400' : 'text-slate-400 hover:text-slate-200'" @click="tabPhong = tab.k"><i :class="tab.icon"></i>{{ tab.ten }}</button></div>
          <div v-if="tabPhong === 'thanh_vien'" class="overflow-hidden flex-1">
            <div class="px-4 py-3 border-b border-slate-800 flex items-center justify-between text-sm font-semibold">
              <span><i class="fa-solid fa-users mr-2 text-brand-400"></i>Thành viên ({{ thanhVien.length }})</span>
              <span v-if="soGioTay > 0" class="nhan bg-amber-400 text-slate-900">
                <i class="fa-solid fa-hand"></i>{{ soGioTay }} giơ tay
              </span>
            </div>
            <div v-if="laGiangVien" class="border-b border-slate-700/50 bg-slate-900/40 p-3 space-y-3" :aria-busy="!!dangCapQuyenTatCa">
              <div>
                <div class="mb-1.5 flex items-center justify-between text-xs font-semibold text-slate-300">
                  <span><i class="fa-solid fa-microphone mr-1.5 text-emerald-400" aria-hidden="true"></i>Mic tất cả</span>
                  <span class="font-normal text-slate-500">{{ soSinhVienTrongPhong }} học sinh</span>
                </div>
                <div class="grid grid-cols-2 gap-2">
                  <button class="min-h-9 rounded-lg bg-emerald-500/15 px-2 text-xs font-semibold text-emerald-300 transition-colors hover:bg-emerald-500/25 disabled:cursor-not-allowed disabled:opacity-50" :disabled="!!dangCapQuyenTatCa || !soSinhVienTrongPhong" aria-label="Cho phép tất cả học sinh sử dụng micro" @click="capQuyenTatCa('mic', true)">
                    <i class="fa-solid mr-1" :class="dangCapQuyenTatCa === 'mic-bat' ? 'fa-spinner fa-spin' : 'fa-microphone'" aria-hidden="true"></i>Cho phép
                  </button>
                  <button class="min-h-9 rounded-lg bg-rose-500/15 px-2 text-xs font-semibold text-rose-300 transition-colors hover:bg-rose-500/25 disabled:cursor-not-allowed disabled:opacity-50" :disabled="!!dangCapQuyenTatCa || !soSinhVienTrongPhong" aria-label="Khóa micro của tất cả học sinh" @click="capQuyenTatCa('mic', false)">
                    <i class="fa-solid mr-1" :class="dangCapQuyenTatCa === 'mic-tat' ? 'fa-spinner fa-spin' : 'fa-microphone-slash'" aria-hidden="true"></i>Khóa
                  </button>
                </div>
              </div>
              <div>
                <div class="mb-1.5 text-xs font-semibold text-slate-300"><i class="fa-solid fa-display mr-1.5 text-indigo-400" aria-hidden="true"></i>Chia sẻ tất cả</div>
                <div class="grid grid-cols-2 gap-2">
                  <button class="min-h-9 rounded-lg bg-indigo-500/15 px-2 text-xs font-semibold text-indigo-300 transition-colors hover:bg-indigo-500/25 disabled:cursor-not-allowed disabled:opacity-50" :disabled="!!dangCapQuyenTatCa || !soSinhVienTrongPhong" aria-label="Cho phép tất cả học sinh chia sẻ màn hình" @click="capQuyenTatCa('chia_se', true)">
                    <i class="fa-solid mr-1" :class="dangCapQuyenTatCa === 'chia_se-bat' ? 'fa-spinner fa-spin' : 'fa-display'" aria-hidden="true"></i>Cho phép
                  </button>
                  <button class="min-h-9 rounded-lg bg-rose-500/15 px-2 text-xs font-semibold text-rose-300 transition-colors hover:bg-rose-500/25 disabled:cursor-not-allowed disabled:opacity-50" :disabled="!!dangCapQuyenTatCa || !soSinhVienTrongPhong" aria-label="Khóa chia sẻ màn hình của tất cả học sinh" @click="capQuyenTatCa('chia_se', false)">
                    <i class="fa-solid mr-1" :class="dangCapQuyenTatCa === 'chia_se-tat' ? 'fa-spinner fa-spin' : 'fa-ban'" aria-hidden="true"></i>Khóa
                  </button>
                </div>
              </div>
            </div>
            <div class="divide-y divide-slate-800 max-h-72 overflow-y-auto">
              <div v-for="tv in thanhVien" :key="tv.ma_tai_khoan" class="px-4 py-2.5 flex items-center justify-between gap-2 text-sm">
                <span class="flex items-center gap-2 min-w-0">
                  <i :class="tv.vai_tro === 'giang_vien' ? 'fa-solid fa-chalkboard-user text-amber-400' : 'fa-solid fa-user-graduate text-sky-400'"></i>
                  <span class="truncate">{{ tv.ho_ten }}</span>
                  <i v-if="tv.gio_tay" class="fa-solid fa-hand text-amber-400 text-xs"></i>
                </span>
                <span class="flex items-center gap-1.5 shrink-0">
                  <span class="text-xs text-slate-500 hidden md:inline">{{ tv.thoi_gian_tham_gia?.split(' ')[0] }}</span>
                  <template v-if="laGiangVien && tv.vai_tro === 'sinh_vien'">
                    <button class="w-7 h-7 rounded-lg flex items-center justify-center text-xs transition"
                      :class="tv.duoc_phep_mac ? 'bg-emerald-500/20 text-emerald-400' : 'bg-slate-800 text-slate-500 hover:text-slate-300'"
                      :title="tv.duoc_phep_mac ? 'Thu hồi quyền mic' : 'Cấp quyền mic'"
                      @click="capQuyenCho(tv, { mic: !tv.duoc_phep_mac, chiaSe: tv.duoc_phep_chia_se })">
                      <i class="fa-solid fa-microphone"></i>
                    </button>
                    <button class="w-7 h-7 rounded-lg flex items-center justify-center text-xs transition"
                      :class="tv.duoc_phep_chia_se ? 'bg-emerald-500/20 text-emerald-400' : 'bg-slate-800 text-slate-500 hover:text-slate-300'"
                      :title="tv.duoc_phep_chia_se ? 'Thu hồi quyền chia sẻ màn hình' : 'Cấp quyền chia sẻ màn hình'"
                      @click="capQuyenCho(tv, { mic: tv.duoc_phep_mac, chiaSe: !tv.duoc_phep_chia_se })">
                      <i class="fa-solid fa-display"></i>
                    </button>
                  </template>
                </span>
              </div>
            </div>
          </div>

          <!-- Chat -->
          <div v-if="tabPhong === 'chat'" class="overflow-hidden flex flex-col flex-1">
            <div class="px-4 py-3 border-b border-slate-800 text-sm font-semibold">
              <i class="fa-regular fa-comments mr-2 text-brand-400"></i>Trò chuyện lớp học
            </div>
            <div class="p-3">
              <div ref="khungChat" class="khung-chat !bg-slate-800/60 p-3 mb-3 text-sm space-y-2">
                <div v-if="!tinNhans.length" class="text-slate-500 text-center text-xs pt-8">Chưa có tin nhắn.</div>
                <div v-for="(tn, i) in tinNhans" :key="i">
                  <div class="text-xs">
                    <span :class="tn.vai_tro === 'giang_vien' ? 'text-amber-400 font-semibold' : 'text-sky-400 font-semibold'">{{ tn.ho_ten }}</span>
                    <span class="text-slate-500 ml-1.5">{{ tn.thoi_gian_gui }}</span>
                  </div>
                  <div class="inline-block bg-slate-700/70 text-slate-100 rounded-xl rounded-tl-sm px-3 py-1.5 mt-0.5 max-w-[85%]">{{ tn.noi_dung }}</div>
                </div>
              </div>
              <form @submit.prevent="guiTinNhan" class="flex gap-2">
                <input v-model="tinNhanMoi"
                  class="min-h-11 flex-1 rounded-xl bg-slate-800 border border-slate-700 px-3.5 py-2.5 text-base sm:text-sm text-white placeholder-slate-500 outline-none focus:border-brand-500"
                  placeholder="Nhập tin nhắn..." />
                <button class="nut-chinh min-h-11 min-w-11 !px-4" aria-label="Gửi tin nhắn"><i class="fa-solid fa-paper-plane" aria-hidden="true"></i></button>
              </form>
            </div>
          </div>
          <div v-if="tabPhong === 'diem_danh'" class="flex-1 overflow-y-auto p-3">
            <template v-if="laGiangVien">
              <button v-if="!phienHienTai" class="w-full min-h-11 flex items-center justify-center gap-2 py-3 mb-4 bg-teal-600 text-white rounded-xl hover:bg-teal-700 text-sm font-medium" @click="moPhienDiemDanh"><i class="fa-solid fa-qrcode" aria-hidden="true"></i>Tạo mã QR điểm danh</button>
              <button v-else class="w-full min-h-11 flex items-center justify-center gap-2 py-3 mb-2 bg-rose-600 text-white rounded-xl hover:bg-rose-700 disabled:cursor-not-allowed disabled:opacity-60 text-sm font-semibold" :disabled="dangDongPhien" aria-label="Đóng phiên điểm danh và đánh dấu vắng" @click="dongPhien"><i class="fa-solid" :class="dangDongPhien ? 'fa-spinner fa-spin' : 'fa-circle-stop'" aria-hidden="true"></i>{{ dangDongPhien ? 'Đang đóng phiên...' : 'Đóng phiên điểm danh' }}</button>
              <p v-if="phienHienTai" class="mb-4 text-center text-xs text-slate-400">Phiên {{ phienHienTai.ma_phien }} · còn {{ demNguocGv }}</p>
              <div class="flex items-center justify-between gap-2 mb-3"><p class="text-xs font-semibold text-slate-300">{{ soCoMat }}/{{ danhSachDiemDanh.length }} đã điểm danh</p><div class="flex gap-1"><span v-if="soVangCoPhep" class="nhan bg-sky-500/15 text-sky-400">{{ soVangCoPhep }} có phép</span><span class="nhan bg-emerald-500/15 text-emerald-400">{{ danhSachDiemDanh.length ? Math.round(soCoMat / danhSachDiemDanh.length * 100) : 0 }}%</span></div></div>
              <div class="space-y-1.5">
                <div v-for="sv in danhSachDiemDanh" :key="sv.ma_sinh_vien" class="flex items-center gap-2.5 px-2.5 py-2 rounded-xl bg-slate-700/40">
                  <div class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold" :class="sv.trang_thai_diem_danh === 'vang_co_phep' ? 'bg-sky-600 text-white' : ['co_mat','di_muon'].includes(sv.trang_thai_diem_danh) ? 'bg-emerald-600 text-white' : 'bg-slate-600 text-slate-300'"><i v-if="sv.trang_thai_diem_danh === 'vang_co_phep'" class="fa-solid fa-file-circle-check"></i><i v-else-if="['co_mat','di_muon'].includes(sv.trang_thai_diem_danh)" class="fa-solid fa-check"></i><span v-else>{{ chuVietHoa(sv.ho_ten) }}</span></div>
                  <span class="flex-1 text-xs text-slate-300 truncate">{{ sv.ho_ten }}</span>
                  <button v-if="sv.trang_thai_diem_danh === 'chua_diem_danh'" class="text-xs px-2 py-1 bg-teal-600/30 text-teal-400 rounded-lg" @click="diemDanhThuCong(sv)">Điểm danh</button>
                  <span v-else class="nhan !text-[10px]" :class="mauBadge(sv.trang_thai_diem_danh)">{{ tenTrangThai(sv.trang_thai_diem_danh) }}</span>
                </div>
              </div>
            </template>
            <div v-else class="text-center py-8"><div class="w-16 h-16 rounded-2xl flex items-center justify-center mx-auto mb-3" :class="daXinPhepVang ? 'bg-sky-500/20' : daDiemDanhToi ? 'bg-emerald-500/20' : 'bg-amber-500/20'"><i :class="daXinPhepVang ? 'fa-solid fa-file-circle-check text-sky-400' : daDiemDanhToi ? 'fa-solid fa-circle-check text-emerald-400' : 'fa-solid fa-qrcode text-amber-400'" class="text-3xl"></i></div><p class="font-semibold" :class="daXinPhepVang ? 'text-sky-400' : daDiemDanhToi ? 'text-emerald-400' : 'text-slate-300'">{{ daXinPhepVang ? 'Đã xin phép vắng' : daDiemDanhToi ? 'Đã điểm danh thành công!' : 'Chưa điểm danh' }}</p><p class="text-slate-500 text-xs mt-1">{{ daXinPhepVang ? 'Bạn không cần quét QR cho buổi học này' : 'Quét mã QR do giảng viên tạo để điểm danh' }}</p></div>
          </div>
        </div>
    </div>
  </div>
</template>

<script>
import QRCode from 'qrcode'
import { markRaw } from 'vue'
import AgoraRTC from 'agora-rtc-sdk-ng'
import api from '../../utils/axios'
import { taoEcho } from '../../utils/echo'
import { useAuthStore } from '../../stores/auth'

export default {
  name: 'phong-hoc',
  data() {
    return {
      auth: useAuthStore(),
      phong: {},
      thanhVien: [],
      tinNhans: [],
      tinNhanMoi: '',
      tabPhong: 'chat',
      bangMobileMo: false,
      cacTabPhong: [
        { k: 'chat', ten: 'Chat', icon: 'fa-solid fa-comment' },
        { k: 'thanh_vien', ten: 'Thành viên', icon: 'fa-solid fa-users' },
        { k: 'diem_danh', ten: 'Điểm danh', icon: 'fa-solid fa-clipboard-check' },
      ],
      thoiLuongPhong: 0,
      dongHoPhong: null,
      soPhut: 5,
      thongTinAgora: null,

      // Phiên điểm danh realtime
      hienQr: false,
      qrDataUrl: '',
      phienHienTai: null,
      giayConLai: 0,
      demNguoc: '00:00',
      demNguocGv: '00:00',
      daDiemDanhToi: false,
      trangThaiDiemDanhToi: 'chua_diem_danh',
      danhSachDiemDanh: [],
      dongHo: null,
      qrTokenTimer: null,

      // Agora
      agoraClient: null,
      micTrack: null,
      camTrack: null,
      manHinhTrack: null,
      dangTaoMic: false,
      kiemTraMicTimer: null,
      daThuPhatLaiMic: false,
      micBat: false,
      camBat: false,
      dangChiaSe: false,

      // Bố cục kiểu Zoom
      cheDoXem: 'loi', // 'loi' = người đang nói (khung lớn + dải nhỏ), 'luoi' = lưới
      ghimUid: null, // người dùng bị ghim lên khung lớn
      dangNoiUid: null, // ai đang nói (volume-indicator)
      remoteVideo: {}, // uid -> videoTrack của người khác
      remoteAudio: {}, // uid -> audioTrack của người khác
      amThanhBiChan: false,
      amThanhDaMoKhoa: false,
      micTrangThai: {}, // uid -> mic bật/tắt
      localUid: null,

      // Quyền trong phòng (GV mặc định đủ quyền; SV chờ GV cấp)
      quyenToi: { la_giang_vien: false, mac: false, chia_se: false, gio_tay: false },
      dangCapQuyenTatCa: null,
      dangDongPhien: false,

      echo: null,
      xuLyMoKhoaAmThanh: null,
    }
  },
  computed: {
    laGiangVien() {
      return this.auth.laGiangVien
    },
    maSinhVienCuaToi() {
      return this.auth.user?.sinh_vien?.id
    },
    soCoMat() {
      return this.danhSachDiemDanh.filter((s) => ['co_mat', 'di_muon'].includes(s.trang_thai_diem_danh)).length
    },
    soVangCoPhep() {
      return this.danhSachDiemDanh.filter((s) => s.trang_thai_diem_danh === 'vang_co_phep').length
    },
    soGioTay() {
      return this.thanhVien.filter((tv) => tv.gio_tay).length
    },
    soSinhVienTrongPhong() {
      return this.thanhVien.filter((tv) => tv.vai_tro === 'sinh_vien').length
    },
    conLaiGiay() {
      return this.giayConLai
    },
    daXinPhepVang() {
      return this.trangThaiDiemDanhToi === 'vang_co_phep'
    },
    thoiLuongPhongHienThi() {
      const gio = Math.floor(this.thoiLuongPhong / 3600)
      const phut = Math.floor((this.thoiLuongPhong % 3600) / 60)
      const giay = this.thoiLuongPhong % 60
      return [gio, phut, giay].map((giaTri) => String(giaTri).padStart(2, '0')).join(':')
    },

    // Danh sách người hiển thị trên các ô video
    danhSachHienThi() {
      const ds = this.thanhVien.map((tv) => ({
        uid: tv.ma_tai_khoan,
        hoTen: tv.ho_ten || 'Thành viên',
        vaiTro: tv.vai_tro,
        gioTay: !!tv.gio_tay,
        dangChiaSe: !!tv.dang_chia_se,
        laToi: Number(tv.ma_tai_khoan) === Number(this.localUid),
      }))
      if (!ds.some((x) => x.uid === this.localUid)) {
        ds.unshift({
          uid: this.localUid,
          hoTen: this.auth.user?.ho_ten || 'Tôi',
          vaiTro: this.laGiangVien ? 'giang_vien' : 'sinh_vien',
          gioTay: !!this.quyenToi.gio_tay,
          dangChiaSe: !!this.dangChiaSe,
          laToi: true,
        })
      }
      return ds
    },

    // Người hiện trên khung lớn: ghim > đang chia sẻ > đang nói > GV > người khác
    uidChinh() {
      const ds = this.danhSachHienThi
      if (this.ghimUid && ds.some((x) => x.uid === this.ghimUid)) return this.ghimUid
      const nguoiChiaSe = ds.find((x) => x.dangChiaSe)
      if (nguoiChiaSe) return nguoiChiaSe.uid
      if (this.dangNoiUid && ds.some((x) => x.uid === this.dangNoiUid)) return this.dangNoiUid
      const gv = ds.find((x) => x.vaiTro === 'giang_vien')
      if (gv) return gv.uid
      const nguoiKhac = ds.find((x) => !x.laToi)
      return nguoiKhac ? nguoiKhac.uid : this.localUid
    },

    // Thứ tự vẽ ô: GV / người chia sẻ lên trước; chế độ người nói đưa người chính lên đầu
    danhSachSapXep() {
      const ds = [...this.danhSachHienThi].sort((a, b) => {
        if (a.vaiTro !== b.vaiTro) return a.vaiTro === 'giang_vien' ? -1 : 1
        if (a.dangChiaSe !== b.dangChiaSe) return a.dangChiaSe ? -1 : 1
        return a.laToi ? 1 : -1
      })
      if (this.cheDoXem === 'loi') {
        const chinh = ds.find((x) => x.uid === this.uidChinh)
        if (chinh) return [chinh, ...ds.filter((x) => x.uid !== chinh.uid)]
      }
      return ds
    },
  },
  async mounted() {
    // Trình duyệt có thể cho phép thu mic nhưng vẫn giữ AudioContext phát âm
    // thanh từ xa ở trạng thái suspended. Mở khóa ngay ở thao tác đầu tiên để
    // giáo viên nghe được mic được sinh viên publish sau khi được cấp quyền.
    this.xuLyMoKhoaAmThanh = () => this.moKhoaAmThanh(false)
    window.addEventListener('pointerdown', this.xuLyMoKhoaAmThanh, { passive: true })
    window.addEventListener('keydown', this.xuLyMoKhoaAmThanh)

    this.localUid = this.auth.user?.id
    const maPhong = this.$route.params.maPhong
    let phienDangMo = null
    try {
      const { data } = await api.post(`/phong/${maPhong}/tham-gia`)
      this.phong = data.phong
      this.thongTinAgora = data.thong_tin_agora
      if (data.quyen) this.quyenToi = data.quyen
      phienDangMo = data.phien_diem_danh
      if (!this.laGiangVien && data.trang_thai_diem_danh_cua_toi) {
        this.trangThaiDiemDanhToi = data.trang_thai_diem_danh_cua_toi
        this.daDiemDanhToi = true
      }
    } catch (e) {
      this.$router.push('/')
      return
    }

    await Promise.all([this.taiThanhVien(), this.taiTinNhan()])
    this.khoiTaoVideo()
    this.langNgheRealtime()
    if (phienDangMo) await this.batDauPhien(phienDangMo)
    this.dongHoPhong = setInterval(() => {
      this.thoiLuongPhong += 1
    }, 1000)
  },
  beforeUnmount() {
    if (this.kiemTraMicTimer) {
      clearTimeout(this.kiemTraMicTimer)
      this.kiemTraMicTimer = null
    }
    if (this.xuLyMoKhoaAmThanh) {
      window.removeEventListener('pointerdown', this.xuLyMoKhoaAmThanh)
      window.removeEventListener('keydown', this.xuLyMoKhoaAmThanh)
      this.xuLyMoKhoaAmThanh = null
    }
    AgoraRTC.onAutoplayFailed = undefined
    AgoraRTC.onAudioContextStateChanged = undefined
    if (this.dongHoPhong) {
      clearInterval(this.dongHoPhong)
      this.dongHoPhong = null
    }
    if (this.dongHo) {
      clearInterval(this.dongHo)
      this.dongHo = null
    }
    this.dungXoayQr()
    this.echo?.leave(`phong.${this.phong.ma_phong}`)
    this.roiAgora()
    api.post(`/phong/${this.phong.ma_phong}/roi`).catch(() => {})
  },
  methods: {
    moBangPhong(tab) {
      this.tabPhong = tab
      // Luôn mở trạng thái sheet. CSS lg:* tự giữ sidebar ở desktop, còn mobile
      // sẽ hiện bottom sheet kể cả khi trình duyệt bật chế độ "Trang web cho máy tính".
      this.bangMobileMo = true
    },

    dongBangPhong() {
      this.bangMobileMo = false
    },

    // ---------- Điều khiển media ----------
    async batTatMic() {
      // Sinh viên chưa được cấp quyền thì không tự bật mic được
      if (!this.quyenToi.mac && !this.laGiangVien) {
        alert('Giáo viên chưa cấp quyền dùng micro. Hãy giơ tay để xin quyền.')
        return
      }

      // iOS Safari và một số Chrome Android chỉ cho AudioContext chạy khi lời gọi
      // nằm trực tiếp trong thao tác chạm của người dùng.
      AgoraRTC.resumeAudioContext()

      if (!this.micTrack) {
        await this.taoVaPhatMic()
        return
      }

      const seBat = !this.micBat
      try {
        const mediaTrack = this.micTrack.getMediaStreamTrack?.()
        if (seBat && mediaTrack?.readyState === 'ended') {
          await this.giaiPhongMic()
          await this.taoVaPhatMic()
          return
        }

        // setMuted ổn định hơn setEnabled với track đang publish và phát đúng
        // user-published/user-unpublished cho các máy còn lại.
        await this.micTrack.setMuted(!seBat)
        this.micBat = seBat
      } catch (loi) {
        this.$toast?.show?.('Không thể thay đổi micro: ' + (loi?.message || ''), { type: 'error', duration: 5000 })
      }
    },

    async giaiPhongMic() {
      if (this.kiemTraMicTimer) {
        clearTimeout(this.kiemTraMicTimer)
        this.kiemTraMicTimer = null
      }
      this.daThuPhatLaiMic = false
      const mic = this.micTrack
      this.micTrack = null
      this.micBat = false
      if (!mic) return

      try { await this.agoraClient?.unpublish(mic) } catch {}
      try { mic.close() } catch {}
    },

    async moKhoaAmThanh(hienThongBao = false) {
      const cacTrack = Object.values(this.remoteAudio).filter(Boolean)
      const tatCaDangPhat = cacTrack.every((track) => track.isPlaying)
      if (this.amThanhDaMoKhoa && tatCaDangPhat && !hienThongBao) return
      try {
        // Không await trước play: Chrome yêu cầu play() nằm trong đúng nhịp xử
        // lý click/pointerdown. Await ở đây có thể làm mất transient user gesture.
        const resumePromise = AgoraRTC.resumeAudioContext()
        cacTrack.forEach((track) => {
          try {
            // Khi người dùng bấm nút Âm thanh, tạo lại duy nhất một player cho
            // từng track. Việc stop trước play tránh âm bị nhân đôi.
            if (hienThongBao) track.stop()
            if (hienThongBao || !track.isPlaying) {
              track.setVolume(100)
              track.play()
            }
          } catch {}
        })

        await resumePromise
        // AudioContext vừa chuyển sang running: thử lại các track chưa chạy.
        cacTrack.forEach((track) => {
          try {
            if (!track.isPlaying) track.play()
          } catch {}
        })
        await new Promise((resolve) => setTimeout(resolve, 100))

        const conTrackChuaPhat = cacTrack.some((track) => !track.isPlaying)
        this.amThanhBiChan = conTrackChuaPhat
        this.amThanhDaMoKhoa = !conTrackChuaPhat
        if (hienThongBao) {
          const thongBao = conTrackChuaPhat
            ? 'Chưa khởi động được âm thanh nhận vào. Cần kiểm tra kết nối và lỗi phát âm thanh.'
            : 'Đã khởi động lại âm thanh lớp học.'
          this.$toast?.show?.(thongBao, { type: conTrackChuaPhat ? 'warning' : 'success', duration: 5000 })
        }
      } catch (loi) {
        this.amThanhBiChan = true
        this.amThanhDaMoKhoa = false
        if (hienThongBao) {
          this.$toast?.show?.('Chưa thể bật âm thanh: ' + (loi?.message || ''), { type: 'warning', duration: 5000 })
        }
      }
    },

    batLaiAmThanh() {
      return this.moKhoaAmThanh(true)
    },

    async batTatCam() {
      if (!this.camTrack) {
        await this.taoVaPhatCamera()
        return
      }

      this.camBat = !this.camBat
      try {
        await this.camTrack.setEnabled(this.camBat)
      } catch (loi) {
        this.camBat = !this.camBat
        this.$toast?.show?.('Không thể thay đổi camera: ' + (loi?.message || ''), { type: 'error', duration: 5000 })
      }
    },

    async batTatChiaSe() {
      if (!this.agoraClient) return
      if (!this.dangChiaSe && !this.quyenToi.chia_se && !this.laGiangVien) {
        alert('Giáo viên chưa cấp quyền chia sẻ màn hình. Hãy giơ tay để xin quyền.')
        return
      }

      try {
        if (this.dangChiaSe) {
          // Dừng chia sẻ: gỡ track màn hình, phát lại camera
          if (this.manHinhTrack) {
            try { await this.agoraClient.unpublish(this.manHinhTrack) } catch {}
            this.manHinhTrack.close()
            this.manHinhTrack = null
          }
          if (this.camTrack && this.camBat) {
            await this.agoraClient.publish(this.camTrack)
          }
          this.dangChiaSe = false
          await api.post(`/phong/${this.phong.ma_phong}/chia-se-trang-thai`, { dang_chia_se: false })
        } else {
          // Bắt đầu chia sẻ: chọn cửa sổ/tab trong trình duyệt
          const manHinh = await AgoraRTC.createScreenVideoTrack()
          this.manHinhTrack = markRaw(manHinh)
          manHinh.on('track-ended', () => {
            this.batTatChiaSe().catch(() => {})
          })
          // RTC mode: mỗi uid 1 dòng video — tạm gỡ camera, phát màn hình
          if (this.camTrack) {
            try { await this.agoraClient.unpublish(this.camTrack) } catch {}
          }
          await this.agoraClient.publish(manHinh)
          this.dangChiaSe = true
          await api.post(`/phong/${this.phong.ma_phong}/chia-se-trang-thai`, { dang_chia_se: true })
        }
      } catch (loi) {
        // Người dùng hủy hộp thoại chọn màn hình
        this.manHinhTrack = null
        this.dangChiaSe = false
      }
    },

    // ---------- Giơ tay & cấp quyền ----------
    async gioTay() {
      const { data } = await api.post(`/phong/${this.phong.ma_phong}/gio-tay`)
      this.quyenToi.gio_tay = data.dang_gio
    },

    async capQuyenCho(tv, { mic, chiaSe }) {
      // Đây là thao tác trực tiếp của giáo viên. Tận dụng nó để mở khóa audio
      // playback trước khi sinh viên publish mic ở thời điểm sau đó.
      this.moKhoaAmThanh(false)
      try {
        await api.post(`/phong/${this.phong.ma_phong}/cap-quyen`, {
          ma_tai_khoan: tv.ma_tai_khoan,
          duoc_phep_mac: mic,
          duoc_phep_chia_se: chiaSe,
        })
        tv.duoc_phep_mac = mic
        tv.duoc_phep_chia_se = chiaSe
        if (mic || chiaSe) tv.gio_tay = false
        if (!chiaSe) tv.dang_chia_se = false
      } catch (error) {
        this.$toast?.show?.(error.response?.data?.message || 'Không thể cập nhật quyền học sinh.', { type: 'error', duration: 5000 })
      }
    },

    async capQuyenTatCa(loaiQuyen, duocPhep) {
      if (!this.soSinhVienTrongPhong || this.dangCapQuyenTatCa) return
      this.dangCapQuyenTatCa = `${loaiQuyen}-${duocPhep ? 'bat' : 'tat'}`

      try {
        const { data } = await api.post(`/phong/${this.phong.ma_phong}/cap-quyen-tat-ca`, {
          loai_quyen: loaiQuyen,
          duoc_phep: duocPhep,
        })

        this.thanhVien.forEach((tv) => {
          if (tv.vai_tro !== 'sinh_vien') return
          if (loaiQuyen === 'mic') tv.duoc_phep_mac = duocPhep
          if (loaiQuyen === 'chia_se') {
            tv.duoc_phep_chia_se = duocPhep
            if (!duocPhep) tv.dang_chia_se = false
          }
          if (duocPhep) tv.gio_tay = false
        })
        this.$toast?.show?.(data.message, { type: 'success', duration: 3500 })
      } catch (error) {
        this.$toast?.show?.(error.response?.data?.message || 'Không thể cập nhật quyền cho tất cả học sinh.', { type: 'error', duration: 5000 })
      } finally {
        this.dangCapQuyenTatCa = null
      }
    },

    // ---------- Bố cục kiểu Zoom ----------
    coVideo(tv) {
      return tv.laToi ? (!!this.camTrack && this.camBat) : !!this.remoteVideo[tv.uid]
    },

    micCua(tv) {
      return tv.laToi ? this.micBat : (this.micTrangThai[tv.uid] ?? true)
    },

    chuVietHoa(hoTen) {
      const w = (hoTen || '').trim().split(/\s+/)
      return ((w[0]?.[0] || '') + (w.length > 1 ? w[w.length - 1][0] : '')).toUpperCase()
    },

    ghim(uid) {
      this.ghimUid = this.ghimUid === uid ? null : uid
    },

    toanManHinh() {
      this.$refs.vungVideo?.requestFullscreen?.()
    },

    // Lớp media bên trong ô video (video Agora phát vào đây, không đè các lớp phủ)
    oMedia(uid) {
      return document.querySelector(`#o-video-${uid} .o-media`)
    },

    // ---------- Video Agora ----------
    khoiTaoVideo() {
      if (!this.thongTinAgora?.app_id) return
      this.giaNhapAgora(this.thongTinAgora)
    },

    async taoVaPhatMic({ thongBaoLoi = true } = {}) {
      if (!this.agoraClient || this.micTrack || this.dangTaoMic) return this.micTrack
      if (!this.laGiangVien && !this.quyenToi.mac) return null

      let mic = null
      this.dangTaoMic = true
      try {
        // Điện thoại thường đã tự tăng gain. Tắt AGC của Agora để tránh khuếch đại
        // kép gây rè/chói; vẫn giữ khử vọng và lọc ồn.
        const cauHinhMic = {
          AEC: true,
          ANS: true,
          AGC: false,
          encoderConfig: 'music_standard',
        }

        try {
          mic = await AgoraRTC.createMicrophoneAudioTrack(cauHinhMic)
        } catch (loiTaoMic) {
          // Một số WebView/điện thoại cũ không nhận đủ media constraints. Chỉ
          // fallback khi không phải lỗi người dùng từ chối quyền microphone.
          if (loiTaoMic?.code === 'PERMISSION_DENIED' || loiTaoMic?.name === 'NotAllowedError') throw loiTaoMic
          mic = await AgoraRTC.createMicrophoneAudioTrack({ encoderConfig: 'music_standard' })
        }

        const mediaTrack = mic.getMediaStreamTrack?.()
        if (!mediaTrack || mediaTrack.readyState !== 'live') {
          throw new Error('Microphone không tạo được audio track đang hoạt động.')
        }

        // Track mới của Agora mặc định đã bật. Không gán trực tiếp
        // MediaStreamTrack.enabled và không gọi setMuted(false) ngay sau publish:
        // hai thao tác đó có thể làm trạng thái SDK và RTCRtpSender lệch nhau khi
        // sinh viên được cấp quyền giữa lúc đang ở trong phòng.
        mic.setVolume(100)
        await this.agoraClient.publish(mic)
        this.micTrack = markRaw(mic)
        this.micBat = true
        this.kiemTraMicDangGui(mic)
        this.$toast?.show?.('Micro đã được bật. Hãy nói để kiểm tra.', { type: 'success', duration: 3000 })
        return mic
      } catch (loi) {
        try { await this.agoraClient?.unpublish(mic) } catch {}
        try { mic?.close() } catch {}
        this.micTrack = null
        this.micBat = false
        if (thongBaoLoi) {
          const thongBao = loi?.code === 'PERMISSION_DENIED'
            ? 'Trình duyệt đang chặn micro. Hãy cấp quyền Microphone trong cài đặt trang rồi thử lại.'
            : 'Không bật được micro: ' + (loi?.msg || loi?.message || 'Lỗi không xác định')
          this.$toast?.show?.(thongBao, { type: 'warning', duration: 7000 })
        }
        return null
      } finally {
        this.dangTaoMic = false
      }
    },

    kiemTraMicDangGui(mic) {
      if (this.kiemTraMicTimer) clearTimeout(this.kiemTraMicTimer)
      this.kiemTraMicTimer = setTimeout(async () => {
        this.kiemTraMicTimer = null
        if (!this.agoraClient || this.micTrack !== mic || !this.micBat) return

        const thongKe = this.agoraClient.getLocalAudioStats?.() || {}
        const dangGui = Number(thongKe.sendBytes || 0) > 0 || Number(thongKe.sendBitrate || 0) > 0
        if (dangGui) return

        // Một số phiên Chrome publish track thứ hai (sau camera) nhưng sender
        // không bắt đầu truyền. Publish lại đúng một lần để đồng bộ sender.
        if (!this.daThuPhatLaiMic) {
          this.daThuPhatLaiMic = true
          try {
            await this.agoraClient.unpublish(mic)
            await this.agoraClient.publish(mic)
            this.kiemTraMicDangGui(mic)
            return
          } catch (loi) {
            this.$toast?.show?.('Agora chưa gửi được micro: ' + (loi?.message || ''), { type: 'warning', duration: 6000 })
          }
        }

        this.$toast?.show?.('Micro đã mở nhưng chưa có dữ liệu âm thanh gửi đi. Hãy kiểm tra thiết bị đầu vào của trình duyệt.', { type: 'warning', duration: 7000 })
      }, 2500)
    },

    async taoVaPhatCamera({ thongBaoLoi = true } = {}) {
      if (!this.agoraClient || this.camTrack) return this.camTrack

      let cam = null
      try {
        cam = await AgoraRTC.createCameraVideoTrack({ encoderConfig: '480p_1' })
        await this.agoraClient.publish(cam)
        this.camTrack = markRaw(cam)
        this.camBat = true
        await this.$nextTick()
        const oLocal = this.oMedia(this.localUid)
        if (oLocal) cam.play(oLocal)
        return cam
      } catch (loi) {
        try { cam?.close() } catch {}
        this.camTrack = null
        this.camBat = false
        if (thongBaoLoi) {
          const thongBao = loi?.code === 'PERMISSION_DENIED'
            ? 'Trình duyệt đang chặn camera. Micro vẫn có thể hoạt động độc lập.'
            : 'Không bật được camera: ' + (loi?.msg || loi?.message || 'Lỗi không xác định')
          this.$toast?.show?.(thongBao, { type: 'warning', duration: 7000 })
        }
        return null
      }
    },

    async giaNhapAgora(t) {
      try {
        AgoraRTC.onAutoplayFailed = () => {
          this.amThanhBiChan = true
          this.amThanhDaMoKhoa = false
        }
        AgoraRTC.onAudioContextStateChanged = (trangThai) => {
          const coAmThanhTuXa = Object.keys(this.remoteAudio).length > 0
          this.amThanhBiChan = coAmThanhTuXa && trangThai !== 'running'
          this.amThanhDaMoKhoa = trangThai === 'running'

          if (trangThai === 'running') {
            Object.values(this.remoteAudio).forEach((track) => {
              try {
                if (track && !track.isPlaying) track.play()
              } catch {}
            })
          }
        }

        const client = AgoraRTC.createClient({ mode: 'rtc', codec: 'vp8' })
        this.agoraClient = markRaw(client)

        // Bật chỉ báo âm lượng để biết ai đang nói (viền xanh + tự chuyển khung lớn)
        client.enableAudioVolumeIndicator()

        client.on('user-published', async (user, mediaType) => {
          await client.subscribe(user, mediaType)
          if (mediaType === 'video') {
            this.remoteVideo = { ...this.remoteVideo, [Number(user.uid)]: markRaw(user.videoTrack) }
            await this.$nextTick()
            let o = this.oMedia(user.uid)
            // Thành viên mới vào mà danh sách chưa có ô → tải lại rồi phát
            if (!o) {
              await this.taiThanhVien()
              await this.$nextTick()
              o = this.oMedia(user.uid)
            }
            if (o) user.videoTrack.play(o)
          }
          if (mediaType === 'audio') {
            const uid = Number(user.uid)
            const trackCu = this.remoteAudio[uid]
            // Luôn dừng player cũ trước khi phát lại. Trường hợp sinh viên
            // unpublish/publish lại có thể trả về cùng một RemoteAudioTrack;
            // bỏ qua play khi object giống nhau sẽ khiến phía giáo viên im tiếng.
            if (trackCu) {
              try { trackCu.stop() } catch {}
            }
            this.remoteAudio = { ...this.remoteAudio, [uid]: markRaw(user.audioTrack) }
            try {
              await AgoraRTC.resumeAudioContext()
              user.audioTrack.setVolume(100)
              user.audioTrack.play()
              this.amThanhBiChan = !user.audioTrack.isPlaying
              this.amThanhDaMoKhoa = user.audioTrack.isPlaying
            } catch {
              this.amThanhBiChan = true
              this.amThanhDaMoKhoa = false
            }
            this.micTrangThai = { ...this.micTrangThai, [uid]: true }
          }
        })

        client.on('user-unpublished', (user, mediaType) => {
          if (mediaType === 'video') {
            try { user.videoTrack?.stop() } catch {}
            const rv = { ...this.remoteVideo }
            delete rv[Number(user.uid)]
            this.remoteVideo = rv
          }
          if (mediaType === 'audio') {
            const uid = Number(user.uid)
            try { this.remoteAudio[uid]?.stop() } catch {}
            const ra = { ...this.remoteAudio }
            delete ra[uid]
            this.remoteAudio = ra
            this.micTrangThai = { ...this.micTrangThai, [uid]: false }
          }
        })

        client.on('user-left', async (user) => {
          const rv = { ...this.remoteVideo }
          delete rv[Number(user.uid)]
          this.remoteVideo = rv
          try { this.remoteAudio[Number(user.uid)]?.stop() } catch {}
          const ra = { ...this.remoteAudio }
          delete ra[Number(user.uid)]
          this.remoteAudio = ra
          const ms = { ...this.micTrangThai }
          delete ms[Number(user.uid)]
          this.micTrangThai = ms
          await this.taiThanhVien()
        })

        // Ai đang nói nhất trong ~2s gần nhất
        client.on('volume-indicator', (volumes) => {
          let toNhat = null
          for (const v of volumes) {
            if (v.level > 10 && (!toNhat || v.level > toNhat.level)) toNhat = v
          }
          this.dangNoiUid = toNhat ? Number(toNhat.uid) : null
          if (toNhat && Number(toNhat.uid) !== Number(this.localUid)) {
            const trackDangNoi = this.remoteAudio[Number(toNhat.uid)]
            if (trackDangNoi && !trackDangNoi.isPlaying) {
              this.amThanhBiChan = true
              this.amThanhDaMoKhoa = false
            }
          }
        })

        await client.join(t.app_id, t.kenh, t.token, this.localUid)

        // Trên thiết bị cảm ứng, đợi người dùng bấm nút Mic để getUserMedia chạy
        // trực tiếp trong thao tác người dùng. Cách này ổn định hơn trên iOS/Android.
        const laThietBiCamUng = window.matchMedia?.('(pointer: coarse)').matches
        if (!laThietBiCamUng && (this.laGiangVien || this.quyenToi.mac)) {
          await this.taoVaPhatMic()
        }
        await this.taoVaPhatCamera()
      } catch (loi) {
        const msg =
          loi?.msg ||
          (loi?.code === 'PERMISSION_DENIED'
            ? 'Trình duyệt đã chặn camera/micro. Bấm biểu tượng khóa trên thanh địa chỉ và cấp quyền.'
            : loi?.code === 'NOT_READABLE'
              ? 'Camera đang bị ứng dụng khác sử dụng.'
              : loi?.message || 'Không tham gia được phòng video.')
        this.$toast?.show?.('Lỗi video Agora: ' + msg, { type: 'error', duration: 8000 })
      }
    },

    roiAgora() {
      try {
        Object.values(this.remoteAudio).forEach((track) => {
          try { track?.stop() } catch {}
        })
        this.manHinhTrack?.close()
        this.camTrack?.close()
        this.micTrack?.close()
        this.agoraClient?.leave()
      } catch {
        // bỏ qua khi rời phòng
      }
      this.manHinhTrack = null
      this.camTrack = null
      this.micTrack = null
      this.agoraClient = null
      this.remoteAudio = {}
      this.remoteVideo = {}
      this.amThanhBiChan = false
      this.amThanhDaMoKhoa = false
      AgoraRTC.onAutoplayFailed = undefined
    },

    // ---------- Realtime WebSocket ----------
    langNgheRealtime() {
      this.echo = taoEcho(this.auth.token)
      const kenh = `phong.${this.phong.ma_phong}`

      this.echo.private(kenh)
        .listen('.phien.diem.danh.mo', (e) => {
          this.batDauPhien(e)
        })
        .listen('.ma.qr.diem.danh.cap.nhat', (e) => {
          this.capNhatQrTuRealtime(e)
        })
        .listen('.diem.danh.thanh.cong', async (e) => {
          if (!this.laGiangVien && Number(this.maSinhVienCuaToi) === Number(e.ma_sinh_vien)) {
            this.daDiemDanhToi = true
            this.trangThaiDiemDanhToi = 'co_mat'
            this.hienQr = false
          }
          if (this.laGiangVien) await this.taiDanhSach()
        })
        .listen('.trang.thai.diem.danh.cap.nhat', async (e) => {
          await this.xuLyTrangThaiDiemDanhCapNhat(e)
        })
        .listen('.phien.diem.danh.dong', async () => {
          this.datTrangThaiPhienDaDong()
          if (this.laGiangVien) await this.taiDanhSach()
        })
        .listen('.tin.nhan.moi', (e) => {
          this.tinNhans.push(e)
          this.$nextTick(() => this.$refs.khungChat?.scrollTo(0, this.$refs.khungChat.scrollHeight))
        })
        .listen('.chia.se.man.hinh', (e) => {
          const tv = this.thanhVien.find((t) => Number(t.ma_tai_khoan) === Number(e.ma_tai_khoan))
          if (tv) tv.dang_chia_se = e.dang_chia_se
          if (Number(e.ma_tai_khoan) === Number(this.localUid)) this.dangChiaSe = e.dang_chia_se
        })
        .listen('.sinh.vien.gio.tay', (e) => {
          const tv = this.thanhVien.find((t) => Number(t.ma_tai_khoan) === Number(e.ma_tai_khoan))
          if (tv) tv.gio_tay = e.dang_gio
          if (Number(e.ma_tai_khoan) === Number(this.auth.user?.id)) this.quyenToi.gio_tay = e.dang_gio
        })
        .listen('.cap.quyen.phong', async (e) => {
          const tv = this.thanhVien.find((t) => Number(t.ma_tai_khoan) === Number(e.ma_tai_khoan))
          if (tv) {
            tv.duoc_phep_mac = e.duoc_phep_mac
            tv.duoc_phep_chia_se = e.duoc_phep_chia_se
            if (e.duoc_phep_mac || e.duoc_phep_chia_se) tv.gio_tay = false
          }

          if (Number(e.ma_tai_khoan) === Number(this.auth.user?.id)) {
            const truocDoDuocPhepMic = this.quyenToi.mac
            this.quyenToi.mac = e.duoc_phep_mac
            this.quyenToi.chia_se = e.duoc_phep_chia_se

            if (!e.duoc_phep_mac && this.micTrack) {
              // Bỏ hẳn track để lần cấp quyền sau nút Mic tạo và publish track mới,
              // tránh trạng thái muted cũ bị kẹt trên Safari/Chrome mobile.
              await this.giaiPhongMic()
            }
            if (e.duoc_phep_mac && !truocDoDuocPhepMic) {
              const laThietBiCamUng = window.matchMedia?.('(pointer: coarse)').matches
              if (!laThietBiCamUng && !this.micTrack) {
                // Trên desktop, tạo/publish ngay để luồng được cấp quyền giữa
                // phiên giống hệt trường hợp tải lại trang với quyền đã có.
                await this.taoVaPhatMic()
              } else {
                this.$toast?.show?.('Giáo viên đã cấp quyền micro. Nhấn nút Mic để bắt đầu nói.', { type: 'success', duration: 5000 })
              }
            }
            if (!e.duoc_phep_chia_se && this.dangChiaSe) {
              await this.batTatChiaSe()
            }
          }
        })
    },

    // ---------- Phiên điểm danh ----------
    async batDauPhien(duLieu) {
      this.phienHienTai = { id: duLieu.id || null, ma_phien: duLieu.ma_phien }
      // Số giây còn lại do server tính sẵn — đếm ngược thuần local,
      // không phụ thuộc đồng hồ máy có bị lệch hay không
      this.giayConLai = Number(duLieu.so_giay) || 300
      const daDuocDuyetPhep = (duLieu.sinh_vien_vang_co_phep || [])
        .some((maSinhVien) => Number(maSinhVien) === Number(this.maSinhVienCuaToi))
      this.trangThaiDiemDanhToi = daDuocDuyetPhep ? 'vang_co_phep' : 'chua_diem_danh'
      this.daDiemDanhToi = daDuocDuyetPhep
      this.hienQr = this.laGiangVien || !daDuocDuyetPhep

      try {
        this.qrDataUrl = await QRCode.toDataURL(duLieu.duong_dan_qr, { width: 220 })
      } catch {
        this.qrDataUrl = ''
      }

      this.batDauDemNguoc()

      if (this.laGiangVien) this.batDauXoayQr()

      if (this.laGiangVien) {
        await this.taiDanhSach()
      }
    },

    batDauDemNguoc() {
      if (this.dongHo) {
        clearInterval(this.dongHo)
        this.dongHo = null
      }
      const tick = () => {
        const s = this.giayConLai
        const mm = String(Math.floor(s / 60)).padStart(2, '0')
        const ss = String(s % 60).padStart(2, '0')
        this.demNguoc = `${mm}:${ss}`
        this.demNguocGv = `${mm}:${ss}`
        if (s <= 0) {
          clearInterval(this.dongHo)
          this.dongHo = null
          this.hienQr = false
          this.dungXoayQr()
          if (this.laGiangVien) this.taiDanhSach()
          return
        }
        this.giayConLai = s - 1
      }
      tick() // hiển thị số đếm ngay lập tức
      this.dongHo = setInterval(tick, 1000)
    },

    batDauXoayQr() {
      this.dungXoayQr()
      this.qrTokenTimer = setInterval(() => this.capNhatQr(), 10000)
    },

    dungXoayQr() {
      if (this.qrTokenTimer) {
        clearInterval(this.qrTokenTimer)
        this.qrTokenTimer = null
      }
    },

    async capNhatQr() {
      if (!this.laGiangVien || !this.phienHienTai) return

      try {
        const id = await this.layIdPhien()
        if (!id) return
        const { data } = await api.get(`/phien-diem-danh/${id}/qr-token`)
        this.qrDataUrl = await QRCode.toDataURL(data.duong_dan_qr, { width: 220 })
      } catch {
        // Phiên vừa đóng hoặc token mới chưa kịp tạo; vòng kế tiếp sẽ thử lại.
      }
    },

    async capNhatQrTuRealtime(duLieu) {
      if (this.laGiangVien || this.daXinPhepVang || !this.phienHienTai || this.phienHienTai.ma_phien !== duLieu.ma_phien) return

      try {
        this.qrDataUrl = await QRCode.toDataURL(duLieu.duong_dan_qr, { width: 220 })
      } catch {
        this.qrDataUrl = ''
      }
    },

    async xuLyTrangThaiDiemDanhCapNhat(duLieu) {
      if (this.laGiangVien) {
        if (this.phienHienTai) await this.taiDanhSach()
        return
      }

      if (Number(this.maSinhVienCuaToi) !== Number(duLieu.ma_sinh_vien)) return
      this.trangThaiDiemDanhToi = duLieu.trang_thai_diem_danh || 'chua_diem_danh'
      this.daDiemDanhToi = this.trangThaiDiemDanhToi !== 'chua_diem_danh'
      this.hienQr = Boolean(this.phienHienTai) && !this.daDiemDanhToi
    },

    // ---- Giảng viên ----
    async moPhienDiemDanh() {
      try {
        const { data } = await api.post('/phien-diem-danh', {
          ma_lich_hoc: this.phong.ma_lich_hoc,
          so_phut: this.soPhut,
        })
        this.batDauPhien({
          id: data.phien.id,
          ma_phien: data.phien.ma_phien,
          duong_dan_qr: data.phien.duong_dan_qr,
          so_giay: data.phien.so_giay,
        })
      } catch (e) {
        alert(e.response?.data?.message || 'Không mở được phiên điểm danh.')
      }
    },

    async taiDanhSach() {
      if (!this.phienHienTai) return
      try {
        if (!this.phienHienTai.id) {
          const { data: ds } = await api.get(`/phien-diem-danh/ma-phien/${this.phienHienTai.ma_phien}/danh-sach`)
          this.phienHienTai = { ...this.phienHienTai, id: ds.phien.id }
        }
        const { data } = await api.get(`/phien-diem-danh/${this.phienHienTai.id}/danh-sach`)
        this.danhSachDiemDanh = data.danh_sach
      } catch {
        // phiên có thể đã đóng
      }
    },

    async diemDanhThuCong(sv) {
      const id = await this.layIdPhien()
      if (!id) return
      await api.post(`/phien-diem-danh/${id}/diem-danh-thu-cong`, {
        ma_sinh_vien: sv.ma_sinh_vien,
        trang_thai: 'co_mat',
      })
      await this.taiDanhSach()
    },

    async suaTrangThai(sv, trangThai) {
      const id = await this.layIdPhien()
      if (!id) return
      await api.put(`/phien-diem-danh/${id}/trang-thai`, {
        ma_sinh_vien: sv.ma_sinh_vien,
        trang_thai_diem_danh: trangThai,
      })
      await this.taiDanhSach()
    },

    async layIdPhien() {
      if (this.phienHienTai?.id) return this.phienHienTai.id
      try {
        const { data } = await api.get(`/phien-diem-danh/ma-phien/${this.phienHienTai.ma_phien}/danh-sach`)
        this.phienHienTai = { ...this.phienHienTai, id: data.phien.id }
        return data.phien.id
      } catch {
        alert('Không xác định được phiên điểm danh.')
        return null
      }
    },

    async dongPhien() {
      const id = await this.layIdPhien()
      if (!id) return
      if (!confirm('Đóng phiên và đánh dấu vắng những sinh viên chưa điểm danh?')) return
      this.dangDongPhien = true

      try {
        const { data } = await api.post(`/phien-diem-danh/${id}/dong`)
        this.datTrangThaiPhienDaDong()
        this.$toast?.show?.(data.message, { type: 'success', duration: 3500 })
        await this.taiDanhSach()
      } catch (error) {
        this.$toast?.show?.(error.response?.data?.message || 'Không thể đóng phiên điểm danh.', { type: 'error', duration: 5000 })
      } finally {
        this.dangDongPhien = false
      }
    },

    datTrangThaiPhienDaDong() {
      if (this.dongHo) {
        clearInterval(this.dongHo)
        this.dongHo = null
      }
      this.phienHienTai = null
      this.hienQr = false
      this.giayConLai = 0
      this.demNguoc = '00:00'
      this.demNguocGv = '00:00'
      this.dungXoayQr()
    },

    async ketThucPhong() {
      if (!confirm('Kết thúc buổi học cho toàn bộ lớp?')) return
      this.roiAgora()
      await api.post(`/phong/${this.phong.ma_phong}/ket-thuc`)
      this.$router.push(this.auth.laAdmin ? { name: 'admin-dashboard' } : { name: 'giang-vien-trang-chu' })
    },

    // ---- Chung ----
    roiPhong() {
      this.$router.back()
    },

    async taiThanhVien() {
      const { data } = await api.get(`/phong/${this.phong.ma_phong}/thanh-vien`)
      this.thanhVien = data.danh_sach
    },

    async taiTinNhan() {
      const { data } = await api.get(`/phong/${this.phong.ma_phong}/tin-nhan`)
      this.tinNhans = data.danh_sach.map((t) => ({
        ...t,
        vai_tro: this.thanhVien.find((v) => Number(v.ma_tai_khoan) === Number(t.ma_tai_khoan))?.vai_tro,
      }))
      this.$nextTick(() => this.$refs.khungChat?.scrollTo(0, this.$refs.khungChat.scrollHeight))
    },

    async guiTinNhan() {
      if (!this.tinNhanMoi.trim()) return
      const noiDung = this.tinNhanMoi
      this.tinNhanMoi = ''
      try {
        const { data } = await api.post(`/phong/${this.phong.ma_phong}/tin-nhan`, { noi_dung: noiDung })
        this.tinNhans.push(data.tin_nhan)
        this.$nextTick(() => this.$refs.khungChat?.scrollTo(0, this.$refs.khungChat.scrollHeight))
      } catch (e) {
        alert(e.response?.data?.message || 'Gửi thất bại.')
      }
    },

    tenTrangThai(t) {
      return { co_mat: 'Có mặt', vang: 'Vắng', di_muon: 'Đi muộn', xin_phep: 'Xin phép', vang_co_phep: 'Vắng có phép', chua_diem_danh: 'Chưa' }[t] || t
    },
    mauBadge(t) {
      return {
        co_mat: 'bg-emerald-500/15 text-emerald-400',
        vang: 'bg-rose-500/15 text-rose-400',
        di_muon: 'bg-amber-500/15 text-amber-400',
        xin_phep: 'bg-violet-500/15 text-violet-400',
        vang_co_phep: 'bg-sky-500/15 text-sky-400',
        chua_diem_danh: 'bg-slate-700 text-slate-400',
      }[t] || 'bg-slate-700 text-slate-400'
    },
  },
}
</script>
