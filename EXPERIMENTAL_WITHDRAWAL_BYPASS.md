# Fitur Eksperimental: Withdrawal Bypass

## Deskripsi
Fitur ini memungkinkan admin untuk membuka kunci (`unlock`) tombol "CAIRKAN SEKARANG" pada halaman `/payment` tanpa harus memenuhi kedua syarat:
1. ✅ Tanggal harus merupakan tanggal pencairan (akhir bulan)
2. ✅ User harus sudah memiliki record kehadiran

## Lokasi Fitur

### 1. Panel Admin (Payment Management)
- **URL**: `http://192.168.2.6:8080/payment-management`
- **Tombol**: Warna **kuning dengan icon unlock** di setiap baris employee
- **Label**: "Bypass"

### 2. User Payment Page  
- **URL**: `http://192.168.2.6:8080/payment`
- **Tombol**: "CAIRKAN SEKARANG" akan unlock ketika bypass aktif

## Cara Kerja

### Mengaktifkan Bypass (Admin)
1. Buka halaman **Payment Management** → `/payment-management`
2. Cari employee yang ingin di-unlock
3. Klik tombol **Bypass** (kuning dengan icon unlock)
4. Konfirmasi pada popup dialog
5. Bypass akan aktif selama **10 menit**
6. Tombol akan menampilkan ring/glow kuning sebagai indikator aktif

### Ketika Bypass Aktif (Employee)
1. Employee buka halaman `/payment`
2. Tombol "CAIRKAN SEKARANG" akan **AKTIF** (hijau) meski tidak memenuhi syarat
3. Employee bisa langsung cairkan tanpa menunggu tanggal/kehadiran
4. Bypass berlaku **10 menit saja**

### Otomatis Reset
- Setelah 10 menit, bypass otomatis expire
- Tombol akan kembali ke status terkunci
- User perlu meminta admin untuk mengaktifkan ulang jika diperlukan

## Technical Details

### Backend Endpoints
```
POST /payment-management/experimental-bypass-withdrawal?user_id={userId}
- Mengaktifkan bypass untuk user tertentu
- Return: success status, username, expires_in_minutes

POST /payment-management/experimental-reset-bypass?user_id={userId}  
- Reset bypass untuk user tertentu
- Return: success status
```

### Session Storage
- Bypass data disimpan di session dengan key: `withdrawal_bypass_{userId}`
- Format:
```php
[
    'user_id' => $userId,
    'token' => $bypassToken,
    'created_at' => timestamp,
    'expires_at' => timestamp,
    'admin_id' => $adminId,
    'admin_username' => $adminUsername
]
```

### Log Entries
Semua aktivitas bypass dicatat di log:
```
[INFO] EXPERIMENTAL: Withdrawal bypass requested by admin {admin} for user {user}
[INFO] Bypass token created for user {userId}: {token}
[INFO] Withdrawal bypass active for user {userId}
[INFO] Withdrawal bypass expired for user {userId}
[INFO] Bypass reset by admin {admin} for user {userId}
```

## Files Modified

1. **Controller**: `app/Controllers/PaymentManagement.php`
   - Method: `experimentalBypassWithdrawal()`
   - Method: `experimentalResetBypass()`

2. **Controller**: `app/Controllers/Payment.php`
   - Updated: `canUserWithdraw()` - sekarang check bypass token terlebih dahulu

3. **View**: `app/Views/payment_management.php`
   - Tombol Bypass dengan styling kuning
   - JavaScript function: `bypassWithdrawal(userId, username)`

## Security Notes

⚠️ **PENTING**:
- Fitur ini hanya bisa diakses oleh **ADMIN**
- Semua aksi dicatat di log untuk audit trail
- Bypass hanya berlaku **10 menit** - tidak permanen
- Session-based - jika server restart, bypass otomatis hilang
- Tidak ada bypass untuk slip gaji (requirement ACC tetap berlaku)

## Testing

### Skenario Test 1: Normal Flow
1. Admin klik Bypass untuk employee X
2. Popup konfirmasi muncul
3. Klik OK
4. Toast success "Bypass aktif untuk [nama] - Berlaku 10 menit"
5. Tombol Bypass menampilkan ring kuning
6. Employee X buka `/payment` → tombol "CAIRKAN SEKARANG" aktif
7. After 10 minutes → bypass auto-expire

### Skenario Test 2: Permissioning
1. Hanya admin yang bisa akses endpoint
2. Employee/non-admin jika coba akses akan dapat 403 Forbidden
3. Unauthenticated users akan redirect ke login

### Skenario Test 3: Multiple Bypasses
1. Admin bisa bypass multiple users sekaligus
2. Masing-masing bypass independent
3. Expiration time berbeda-beda per user

## Future Improvements

- [ ] Tambahkan database table untuk bypass history (saat ini hanya session)
- [ ] Tambahkan manual reset button untuk admin
- [ ] Customize bypass duration (saat ini hardcoded 10 menit)
- [ ] Tambahkan notification ke employee saat bypass diaktifkan
- [ ] Tambahkan bypass limit per admin per hari

## Maintenance

### Jika bypass tidak bekerja:
1. Check browser console untuk JavaScript errors
2. Check PHP logs untuk endpoint errors
3. Verify admin user memiliki role='admin'
4. Verify employee user tersebut ada di database

### Jika ingin disable fitur:
- Comment out method `experimentalBypassWithdrawal()` di PaymentManagement
- Remove tombol Bypass dari view
- Revert changes di `canUserWithdraw()` method
