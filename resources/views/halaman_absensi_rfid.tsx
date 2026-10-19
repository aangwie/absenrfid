import React, { useState, useEffect, useRef } from 'react';
import {
  Users,
  UserCheck,
  UserX,
  Clock,
  CreditCard,
  Search,
  Filter,
  CheckCircle2,
  XCircle,
  AlertTriangle,
  Volume2,
  VolumeX,
  Database,
  Cpu,
  Sparkles,
  LogOut,
  LogIn,
  BarChart3,
  Wifi,
  Radio,
  Code,
  Usb,
  UserPlus,
  Plus,
  X,
  Check,
  FileSpreadsheet
} from 'lucide-react';
import {
  BarChart,
  Bar,
  XAxis,
  YAxis,
  CartesianGrid,
  Tooltip,
  ResponsiveContainer,
  PieChart,
  Pie,
  Cell,
  Legend
} from 'recharts';

// Master Data Siswa Simulasi (Representasi Data dari Database MySQL `tb_siswa`)
const INITIAL_STUDENTS = [
  {
    id: '1',
    nisn: '0082234749',
    rfidTag: '2234749610', // Tag RFID Utama Sesuai permintaan uji coba
    nama: 'Muhammad Rizky Pratama',
    kelas: 'XII RPL 1',
    gender: 'L',
    avatar: 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?w=150&auto=format&fit=crop&q=80',
    jamMasuk: '-',
    jamPulang: '-',
    statusMasuk: 'Belum Absen',
    statusPulang: '-',
    statusHariIni: 'Belum Absen'
  },
  {
    id: '2',
    nisn: '0081234567',
    rfidTag: '1084291',
    nama: 'Budi Santoso',
    kelas: 'XII RPL 1',
    gender: 'L',
    avatar: 'https://images.unsplash.com/photo-1570295999919-56ceb5ecca61?w=150&auto=format&fit=crop&q=80',
    jamMasuk: '06:45:12',
    jamPulang: '15:30:05',
    statusMasuk: 'Tepat Waktu',
    statusPulang: 'Selesai',
    statusHariIni: 'Pulang'
  },
  {
    id: '3',
    nisn: '0082345678',
    rfidTag: '2094812',
    nama: 'Siti Aminah',
    kelas: 'XII RPL 2',
    gender: 'P',
    avatar: 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?w=150&auto=format&fit=crop&q=80',
    jamMasuk: '06:50:00',
    jamPulang: '-',
    statusMasuk: 'Tepat Waktu',
    statusPulang: 'Belum',
    statusHariIni: 'Masuk'
  },
  {
    id: '4',
    nisn: '0083456789',
    rfidTag: '3019284',
    nama: 'Ahmad Rizky',
    kelas: 'XI TKJ 2',
    gender: 'L',
    avatar: 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=150&auto=format&fit=crop&q=80',
    jamMasuk: '07:18:45',
    jamPulang: '-',
    statusMasuk: 'Terlambat',
    statusPulang: 'Belum',
    statusHariIni: 'Masuk'
  },
  {
    id: '5',
    nisn: '0084567890',
    rfidTag: '4082910',
    nama: 'Dewi Lestari',
    kelas: 'X AKL 3',
    gender: 'P',
    avatar: 'https://images.unsplash.com/photo-1580489944761-15a19d654956?w=150&auto=format&fit=crop&q=80',
    jamMasuk: '06:30:10',
    jamPulang: '15:15:22',
    statusMasuk: 'Tepat Waktu',
    statusPulang: 'Selesai',
    statusHariIni: 'Pulang'
  },
  {
    id: '6',
    nisn: '0086789012',
    rfidTag: '6012938',
    nama: 'Anisa Rahmawati',
    kelas: 'X IPA 1',
    gender: 'P',
    avatar: 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=150&auto=format&fit=crop&q=80',
    jamMasuk: '-',
    jamPulang: '-',
    statusMasuk: 'Belum Absen',
    statusPulang: '-',
    statusHariIni: 'Belum Absen'
  }
];

const HOURLY_ACTIVITY_DATA = [
  { jam: '06:00', masuk: 12, pulang: 0 },
  { jam: '06:30', masuk: 45, pulang: 0 },
  { jam: '07:00', masuk: 23, pulang: 0 },
  { jam: '07:30', masuk: 5, pulang: 0 },
  { jam: '14:30', masuk: 0, pulang: 15 },
  { jam: '15:00', masuk: 0, pulang: 52 },
  { jam: '15:30', masuk: 0, pulang: 18 }
];

export default function App() {
  // State Utama
  const [students, setStudents] = useState(INITIAL_STUDENTS);
  const [rfidInput, setRfidInput] = useState('');
  const [searchQuery, setSearchQuery] = useState('');
  const [selectedClass, setSelectedClass] = useState('Semua');
  const [activeTab, setActiveTab] = useState('semua');
  const [currentTime, setCurrentTime] = useState(new Date());
  const [soundEnabled, setSoundEnabled] = useState(true);
  const [showDbModal, setShowDbModal] = useState(false);
  const [showAddStudentModal, setShowAddStudentModal] = useState(false);
  const [showAnalytics, setShowAnalytics] = useState(false);
  const [usbStatus, setUsbStatus] = useState('connected'); // 'connected', 'scanning'

  // Form Tambah Siswa Baru
  const [newStudent, setNewStudent] = useState({
    nama: '',
    nisn: '',
    rfidTag: '',
    kelas: 'XII RPL 1',
    gender: 'L'
  });

  // State Notifikasi Real-time Scan RFID
  const [lastScanResult, setLastScanResult] = useState(null);

  // Ref Input RFID untuk menjaga Auto-Focus Layaknya Mesin Kios Absensi Hardware USB Reader
  const rfidInputRef = useRef(null);

  const playSound = (type) => {
    if (!soundEnabled) return;
    try {
      const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
      const osc = audioCtx.createOscillator();
      const gain = audioCtx.createGain();
      osc.connect(gain);
      gain.connect(audioCtx.destination);

      if (type === 'masuk') {
        // High dual-tone beep (Tap Masuk Sukses)
        osc.frequency.setValueAtTime(880, audioCtx.currentTime);
        osc.frequency.setValueAtTime(1174.66, audioCtx.currentTime + 0.1);
        gain.gain.setValueAtTime(0.15, audioCtx.currentTime);
        gain.gain.exponentialRampToValueAtTime(0.01, audioCtx.currentTime + 0.3);
        osc.start();
        osc.stop(audioCtx.currentTime + 0.3);
      } else if (type === 'pulang') {
        // Melodic chime tone (Tap Pulang Sukses)
        osc.frequency.setValueAtTime(587.33, audioCtx.currentTime);
        osc.frequency.setValueAtTime(880, audioCtx.currentTime + 0.12);
        gain.gain.setValueAtTime(0.15, audioCtx.currentTime);
        gain.gain.exponentialRampToValueAtTime(0.01, audioCtx.currentTime + 0.35);
        osc.start();
        osc.stop(audioCtx.currentTime + 0.35);
      } else {
        // Low error warning beep
        osc.frequency.setValueAtTime(220, audioCtx.currentTime);
        gain.gain.setValueAtTime(0.2, audioCtx.currentTime);
        gain.gain.exponentialRampToValueAtTime(0.01, audioCtx.currentTime + 0.4);
        osc.start();
        osc.stop(audioCtx.currentTime + 0.4);
      }
    } catch (e) {
      console.log('Audio error:', e);
    }
  };

  useEffect(() => {
    const timer = setInterval(() => setCurrentTime(new Date()), 1000);
    return () => clearInterval(timer);
  }, []);

  // Menjaga fokus input otomatis agar USB RFID Reader (Keyboard Emulator) langsung mengetik
  useEffect(() => {
    const focusInterval = setInterval(() => {
      if (
        document.activeElement !== rfidInputRef.current &&
        !showDbModal &&
        !showAddStudentModal
      ) {
        rfidInputRef.current?.focus();
      }
    }, 1500);
    return () => clearInterval(focusInterval);
  }, [showDbModal, showAddStudentModal]);

  const processRfidScan = (scannedTagOrNisn) => {
    const tag = scannedTagOrNisn.trim();
    if (!tag) return;

    setUsbStatus('scanning');
    setTimeout(() => setUsbStatus('connected'), 600);

    const now = new Date();
    const timeString = now.toTimeString().split(' ')[0]; // HH:MM:SS
    
    // Toleransi Jam Masuk Sekolah (Jam 07:00:00)
    const jamBatasTerlambat = "07:00:00";
    const isLate = timeString > jamBatasTerlambat;

    // Cari Siswa di Database berdasarkan Tag RFID atau NISN
    const studentIndex = students.findIndex(
      s => s.rfidTag === tag || s.nisn === tag
    );

    if (studentIndex !== -1) {
      const student = students[studentIndex];
      let updatedStudents = [...students];

      if (student.statusHariIni === 'Belum Absen') {
        // TAP PERTAMA -> ABSEN MASUK
        const statusDetail = isLate ? 'Terlambat' : 'Tepat Waktu';
        
        updatedStudents[studentIndex] = {
          ...student,
          jamMasuk: timeString,
          statusMasuk: statusDetail,
          statusHariIni: 'Masuk'
        };

        playSound('masuk');
        setLastScanResult({
          success: true,
          type: 'MASUK',
          siswa: student,
          jam: timeString,
          status: statusDetail,
          pesan: `Selamat Datang, ${student.nama}! Absen Masuk Berhasil.`
        });

      } else if (student.statusHariIni === 'Masuk') {
        // TAP KEDUA -> ABSEN PULANG
        updatedStudents[studentIndex] = {
          ...student,
          jamPulang: timeString,
          statusPulang: 'Selesai',
          statusHariIni: 'Pulang'
        };

        playSound('pulang');
        setLastScanResult({
          success: true,
          type: 'PULANG',
          siswa: student,
          jam: timeString,
          status: 'Selesai',
          pesan: `Selamat Jalan, ${student.nama}! Absen Pulang Berhasil.`
        });

      } else {
        // SUDAH TAP MASUK DAN PULANG
        playSound('error');
        setLastScanResult({
          success: false,
          type: 'ALREADY_COMPLETED',
          siswa: student,
          jam: timeString,
          status: 'Sudah Pulang',
          pesan: `Siswa ${student.nama} sudah melakukan Absen Masuk & Pulang hari ini.`
        });
      }

      setStudents(updatedStudents);

    } else {
      // KARTU TIDAK TERDAFTAR
      playSound('error');
      setLastScanResult({
        success: false,
        type: 'UNKNOWN_CARD',
        siswa: null,
        rfidTag: tag,
        jam: timeString,
        pesan: `Kartu RFID ID [${tag}] Tidak Terdaftar di Database Sekolah!`
      });
    }

    setRfidInput('');
  };

  const handleRfidSubmit = (e) => {
    e.preventDefault();
    processRfidScan(rfidInput);
  };

  const handleAddStudent = (e) => {
    e.preventDefault();
    if (!newStudent.nama || !newStudent.rfidTag || !newStudent.nisn) return;

    const createdStudent = {
      id: Date.now().toString(),
      nisn: newStudent.nisn,
      rfidTag: newStudent.rfidTag,
      nama: newStudent.nama,
      kelas: newStudent.kelas,
      gender: newStudent.gender,
      avatar: newStudent.gender === 'L'
        ? 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?w=150&auto=format&fit=crop&q=80'
        : 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?w=150&auto=format&fit=crop&q=80',
      jamMasuk: '-',
      jamPulang: '-',
      statusMasuk: 'Belum Absen',
      statusPulang: '-',
      statusHariIni: 'Belum Absen'
    };

    setStudents([createdStudent, ...students]);
    setNewStudent({ nama: '', nisn: '', rfidTag: '', kelas: 'XII RPL 1', gender: 'L' });
    setShowAddStudentModal(false);

    setLastScanResult({
      success: true,
      type: 'REGISTERED',
      siswa: createdStudent,
      jam: new Date().toTimeString().split(' ')[0],
      status: 'Terdaftar',
      pesan: `Siswa Baru ${createdStudent.nama} (RFID: ${createdStudent.rfidTag}) Berhasil Didaftarkan!`
    });
  };

  const filteredStudents = students.filter(student => {
    const matchSearch =
      student.nama.toLowerCase().includes(searchQuery.toLowerCase()) ||
      student.nisn.includes(searchQuery) ||
      student.rfidTag.includes(searchQuery);

    const matchClass =
      selectedClass === 'Semua' || student.kelas === selectedClass;

    if (activeTab === 'masuk') return matchSearch && matchClass && (student.statusHariIni === 'Masuk' || student.statusHariIni === 'Pulang');
    if (activeTab === 'pulang') return matchSearch && matchClass && student.statusHariIni === 'Pulang';
    if (activeTab === 'terlambat') return matchSearch && matchClass && student.statusMasuk === 'Terlambat';
    if (activeTab === 'belum') return matchSearch && matchClass && student.statusHariIni === 'Belum Absen';

    return matchSearch && matchClass;
  });

  // Hitung Statistik
  const totalSiswa = students.length;
  const totalMasuk = students.filter(s => s.statusHariIni === 'Masuk' || s.statusHariIni === 'Pulang').length;
  const totalPulang = students.filter(s => s.statusHariIni === 'Pulang').length;
  const totalTerlambat = students.filter(s => s.statusMasuk === 'Terlambat').length;
  const totalBelum = students.filter(s => s.statusHariIni === 'Belum Absen').length;

  const pieData = [
    { name: 'Tepat Waktu', value: totalMasuk - totalTerlambat, color: '#10B981' },
    { name: 'Terlambat', value: totalTerlambat, color: '#F59E0B' },
    { name: 'Belum Absen', value: totalBelum, color: '#EF4444' }
  ];

  const kelasList = ['Semua', ...new Set(students.map(s => s.kelas))];

  return (
    <div className="min-h-screen bg-slate-900 text-slate-100 font-sans p-3 sm:p-6 flex flex-col gap-6">
      
      <header className="bg-slate-800/90 border border-slate-700 rounded-2xl p-4 sm:p-6 backdrop-blur-md shadow-xl flex flex-col lg:flex-row items-center justify-between gap-4">
        <div className="flex items-center gap-4 text-center lg:text-left">
          <div className="w-14 h-14 bg-gradient-to-tr from-blue-600 to-indigo-500 rounded-2xl flex items-center justify-center shadow-lg shadow-blue-500/20 shrink-0">
            <Radio className="w-8 h-8 text-white animate-pulse" />
          </div>
          <div>
            <div className="flex flex-wrap items-center justify-center lg:justify-start gap-2">
              <span className="bg-blue-500/20 text-blue-400 text-xs font-semibold px-2.5 py-0.5 rounded-full border border-blue-500/30 flex items-center gap-1">
                <Usb className="w-3.5 h-3.5" /> USB HID RFID Reader
              </span>
              <span className="flex items-center gap-1 bg-emerald-500/20 text-emerald-400 text-xs px-2.5 py-0.5 rounded-full border border-emerald-500/30">
                <span className="w-2 h-2 rounded-full bg-emerald-400 animate-ping"></span>
                Reader Active
              </span>
            </div>
            <h1 className="text-xl sm:text-2xl font-bold tracking-tight text-white mt-1">
              Sistem Absensi Siswa RFID
            </h1>
            <p className="text-slate-400 text-xs sm:text-sm">
              Kios Gate Otomatis Absen Masuk & Pulang Sekolah
            </p>
          </div>
        </div>

        {/* Real-time Clock & Sound Toggle */}
        <div className="flex items-center gap-3 bg-slate-900/80 px-4 py-3 rounded-xl border border-slate-700/80 w-full sm:w-auto justify-between sm:justify-end">
          <div className="flex items-center gap-3">
            <Clock className="w-5 h-5 text-indigo-400 shrink-0" />
            <div>
              <div className="text-[11px] text-slate-400 capitalize">
                {currentTime.toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' })}
              </div>
              <div className="text-xl font-mono font-bold text-indigo-300">
                {currentTime.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' })} WIB
              </div>
            </div>
          </div>

          <div className="h-8 w-px bg-slate-700 mx-2 hidden sm:block"></div>

          <button
            onClick={() => setSoundEnabled(!soundEnabled)}
            className={`p-2.5 rounded-lg border transition-all ${
              soundEnabled
                ? 'bg-indigo-600/20 text-indigo-400 border-indigo-500/40 hover:bg-indigo-600/30'
                : 'bg-slate-800 text-slate-500 border-slate-700 hover:text-slate-300'
            }`}
            title={soundEnabled ? "Suara Aktif" : "Suara Muted"}
          >
            {soundEnabled ? <Volume2 className="w-5 h-5" /> : <VolumeX className="w-5 h-5" />}
          </button>
        </div>
      </header>

      <div className="grid grid-cols-2 lg:grid-cols-5 gap-3 sm:gap-4">
        {/* Total Siswa */}
        <div className="bg-slate-800/70 border border-slate-700/80 rounded-xl p-4 flex items-center justify-between">
          <div>
            <p className="text-xs font-medium text-slate-400">Total Siswa</p>
            <h3 className="text-2xl font-bold text-white mt-1">{totalSiswa}</h3>
            <span className="text-[11px] text-slate-500">Terdaftar di DB</span>
          </div>
          <div className="p-3 bg-blue-500/10 text-blue-400 rounded-xl">
            <Users className="w-6 h-6" />
          </div>
        </div>

        {/* Total Masuk */}
        <div className="bg-slate-800/70 border border-slate-700/80 rounded-xl p-4 flex items-center justify-between">
          <div>
            <p className="text-xs font-medium text-slate-400">Siswa Masuk</p>
            <h3 className="text-2xl font-bold text-emerald-400 mt-1">{totalMasuk}</h3>
            <span className="text-[11px] text-emerald-500">
              {totalSiswa > 0 ? Math.round((totalMasuk / totalSiswa) * 100) : 0}% Kehadiran
            </span>
          </div>
          <div className="p-3 bg-emerald-500/10 text-emerald-400 rounded-xl">
            <UserCheck className="w-6 h-6" />
          </div>
        </div>

        {/* Total Pulang */}
        <div className="bg-slate-800/70 border border-slate-700/80 rounded-xl p-4 flex items-center justify-between">
          <div>
            <p className="text-xs font-medium text-slate-400">Sudah Pulang</p>
            <h3 className="text-2xl font-bold text-indigo-400 mt-1">{totalPulang}</h3>
            <span className="text-[11px] text-indigo-400">Gate Out Active</span>
          </div>
          <div className="p-3 bg-indigo-500/10 text-indigo-400 rounded-xl">
            <LogOut className="w-6 h-6" />
          </div>
        </div>

        {/* Total Terlambat */}
        <div className="bg-slate-800/70 border border-slate-700/80 rounded-xl p-4 flex items-center justify-between">
          <div>
            <p className="text-xs font-medium text-slate-400">Terlambat</p>
            <h3 className="text-2xl font-bold text-amber-400 mt-1">{totalTerlambat}</h3>
            <span className="text-[11px] text-amber-500">&gt; 07:00 WIB</span>
          </div>
          <div className="p-3 bg-amber-500/10 text-amber-400 rounded-xl">
            <AlertTriangle className="w-6 h-6" />
          </div>
        </div>

        {/* Total Belum Absen */}
        <div className="bg-slate-800/70 border border-slate-700/80 rounded-xl p-4 flex items-center justify-between col-span-2 lg:col-span-1">
          <div>
            <p className="text-xs font-medium text-slate-400">Belum Absen</p>
            <h3 className="text-2xl font-bold text-rose-400 mt-1">{totalBelum}</h3>
            <span className="text-[11px] text-rose-500">Tanpa Keterangan</span>
          </div>
          <div className="p-3 bg-rose-500/10 text-rose-400 rounded-xl">
            <UserX className="w-6 h-6" />
          </div>
        </div>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        {/* USB RFID Reader Input Box */}
        <div className="lg:col-span-2 bg-gradient-to-br from-slate-800 to-slate-800/90 border border-slate-700 rounded-2xl p-5 shadow-lg flex flex-col justify-between gap-4">
          <div>
            <div className="flex items-center justify-between mb-3">
              <div className="flex items-center gap-2">
                <Usb className="w-5 h-5 text-blue-400" />
                <h2 className="text-base font-semibold text-white">Area Tap Reader RFID USB</h2>
              </div>
              <span className={`text-xs px-2.5 py-1 rounded-full flex items-center gap-1.5 transition-all ${
                usbStatus === 'scanning'
                  ? 'bg-amber-500/20 text-amber-300 border border-amber-500/40 animate-pulse'
                  : 'bg-indigo-500/20 text-indigo-300 border border-indigo-500/30'
              }`}>
                <Cpu className="w-3.5 h-3.5" />
                {usbStatus === 'scanning' ? 'Membaca Kartu...' : 'Auto-Focus USB Active'}
              </span>
            </div>

            <p className="text-xs text-slate-400 mb-3">
              Tempelkan Kartu RFID pada sensor reader USB. Sistem akan otomatis mendeteksi input keyboard emulator tanpa perlu klik tetikus.
            </p>

            {/* Input Form RFID */}
            <form onSubmit={handleRfidSubmit} className="relative">
              <input
                ref={rfidInputRef}
                type="password"
                autoComplete="off"
                spellCheck={false}
                value={rfidInput}
                onChange={(e) => setRfidInput(e.target.value)}
                placeholder="Tempelkan kartu RFID atau ketik ID: 2234749610 lalu tekan Enter..."
                className="w-full bg-slate-900/90 border-2 border-indigo-500/50 focus:border-indigo-400 rounded-xl px-4 py-3.5 pl-11 text-white placeholder-slate-500 focus:outline-none focus:ring-4 focus:ring-indigo-500/20 text-sm sm:text-base font-mono transition-all"
              />
              <CreditCard className="w-5 h-5 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2" />
              <button
                type="submit"
                className="absolute right-2 top-1/2 -translate-y-1/2 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold px-4 py-2 rounded-lg transition-all"
              >
                Proses Tap
              </button>
            </form>
          </div>

          {/* Preset Simulator & Test ID "2234749610" */}
          <div className="border-t border-slate-700/60 pt-4">
            <p className="text-xs text-slate-400 mb-2 font-medium flex items-center gap-1.5">
              <Sparkles className="w-3.5 h-3.5 text-amber-400" /> Tombol Simulasi Pengujian Kuis Tap RFID (Uji Kartu "2234749610"):
            </p>
            <div className="flex flex-wrap gap-2">
              <button
                onClick={() => processRfidScan('2234749610')}
                className="bg-indigo-600/30 hover:bg-indigo-600/50 border border-indigo-500/60 text-xs px-3 py-2 rounded-lg text-indigo-200 font-medium flex items-center gap-1.5 transition-all shadow-sm"
              >
                <LogIn className="w-3.5 h-3.5 text-indigo-400" />
                Tap RFID 2234749610 (Muhammad Rizky)
              </button>

              <button
                onClick={() => processRfidScan('1084291')}
                className="bg-slate-700/80 hover:bg-emerald-600/30 hover:border-emerald-500/50 border border-slate-600 text-xs px-3 py-2 rounded-lg text-slate-200 flex items-center gap-1.5 transition-all"
              >
                <LogIn className="w-3.5 h-3.5 text-emerald-400" />
                Tap Budi (Masuk/Pulang)
              </button>
              
              <button
                onClick={() => processRfidScan('2094812')}
                className="bg-slate-700/80 hover:bg-emerald-600/30 hover:border-emerald-500/50 border border-slate-600 text-xs px-3 py-2 rounded-lg text-slate-200 flex items-center gap-1.5 transition-all"
              >
                <LogIn className="w-3.5 h-3.5 text-emerald-400" />
                Tap Siti (Masuk)
              </button>

              <button
                onClick={() => processRfidScan('999999999')}
                className="bg-slate-700/80 hover:bg-rose-600/30 hover:border-rose-500/50 border border-slate-600 text-xs px-3 py-2 rounded-lg text-slate-200 flex items-center gap-1.5 transition-all"
              >
                <XCircle className="w-3.5 h-3.5 text-rose-400" />
                Tap Kartu Tidak Dikenal
              </button>
            </div>
          </div>
        </div>

        {/* Display Visual Hasil Scan Terakhir */}
        <div className="bg-slate-800/80 border border-slate-700 rounded-2xl p-5 flex flex-col justify-between">
          <div className="flex items-center justify-between mb-2">
            <span className="text-xs text-slate-400 font-semibold tracking-wider uppercase">Monitor Tampilan Gate Kios</span>
            <span className="text-[11px] bg-slate-700 text-slate-300 px-2 py-0.5 rounded">Live Monitor</span>
          </div>

          {lastScanResult ? (
            <div className={`p-4 rounded-xl border flex flex-col items-center text-center gap-3 transition-all ${
              lastScanResult.success
                ? lastScanResult.type === 'MASUK'
                  ? 'bg-emerald-950/50 border-emerald-500/50 text-emerald-200'
                  : lastScanResult.type === 'PULANG'
                  ? 'bg-indigo-950/50 border-indigo-500/50 text-indigo-200'
                  : 'bg-blue-950/50 border-blue-500/50 text-blue-200'
                : 'bg-rose-950/50 border-rose-500/50 text-rose-200'
            }`}>
              {lastScanResult.success ? (
                <>
                  <img
                    src={lastScanResult.siswa?.avatar}
                    alt={lastScanResult.siswa?.nama}
                    className="w-16 h-16 rounded-full border-2 border-emerald-400 shadow-md object-cover"
                  />
                  <div>
                    <h4 className="font-bold text-lg text-white">{lastScanResult.siswa?.nama}</h4>
                    <p className="text-xs text-slate-300">
                      NISN: {lastScanResult.siswa?.nisn} | Kelas: {lastScanResult.siswa?.kelas}
                    </p>
                    <div className="mt-2 flex items-center justify-center gap-2">
                      <span className={`text-xs px-2.5 py-1 rounded-full font-semibold ${
                        lastScanResult.type === 'MASUK'
                          ? 'bg-emerald-500 text-slate-950'
                          : 'bg-indigo-500 text-white'
                      }`}>
                        ABSEN {lastScanResult.type} ({lastScanResult.jam})
                      </span>
                      <span className="text-xs bg-slate-900/80 px-2.5 py-1 rounded-full border border-slate-700">
                        {lastScanResult.status}
                      </span>
                    </div>
                  </div>
                </>
              ) : (
                <div className="py-2">
                  <div className="w-12 h-12 bg-rose-500/20 text-rose-400 rounded-full flex items-center justify-center mx-auto mb-2">
                    <XCircle className="w-7 h-7" />
                  </div>
                  <h4 className="font-bold text-base text-rose-300">Gagal Memproses Kartu</h4>
                  <p className="text-xs text-rose-200/80 mt-1">{lastScanResult.pesan}</p>
                </div>
              )}
            </div>
          ) : (
            <div className="h-full flex flex-col items-center justify-center text-center p-6 border-2 border-dashed border-slate-700/80 rounded-xl bg-slate-900/40">
              <Radio className="w-10 h-10 text-slate-600 mb-2 animate-bounce" />
              <p className="text-sm font-medium text-slate-400">Siap Menunggu Tap RFID USB...</p>
              <p className="text-xs text-slate-500 mt-1">Tempelkan kartu siswa pada sensor USB reader</p>
            </div>
          )}

          <div className="mt-3 text-center">
            <span className="text-[11px] text-slate-500">Notifikasi otomatis terbarui tiap kali pengetapan</span>
          </div>
        </div>
      </div>

      <div className="bg-slate-800/80 border border-slate-700 rounded-2xl p-5 shadow-xl flex flex-col gap-4">
        
        {/* Header Tab & Actions */}
        <div className="flex flex-col md:flex-row items-stretch md:items-center justify-between gap-4 pb-2 border-b border-slate-700/80">
          
          {/* Tabs Filter Status */}
          <div className="flex items-center gap-1 bg-slate-900/80 p-1 rounded-xl border border-slate-700/60 overflow-x-auto">
            <button
              onClick={() => setActiveTab('semua')}
              className={`px-3.5 py-2 rounded-lg text-xs font-semibold whitespace-nowrap transition-all ${
                activeTab === 'semua'
                  ? 'bg-indigo-600 text-white shadow'
                  : 'text-slate-400 hover:text-white'
              }`}
            >
              Semua Data ({students.length})
            </button>
            <button
              onClick={() => setActiveTab('masuk')}
              className={`px-3.5 py-2 rounded-lg text-xs font-semibold whitespace-nowrap transition-all ${
                activeTab === 'masuk'
                  ? 'bg-emerald-600 text-white shadow'
                  : 'text-slate-400 hover:text-white'
              }`}
            >
              Sudah Masuk ({totalMasuk})
            </button>
            <button
              onClick={() => setActiveTab('pulang')}
              className={`px-3.5 py-2 rounded-lg text-xs font-semibold whitespace-nowrap transition-all ${
                activeTab === 'pulang'
                  ? 'bg-indigo-600 text-white shadow'
                  : 'text-slate-400 hover:text-white'
              }`}
            >
              Sudah Pulang ({totalPulang})
            </button>
            <button
              onClick={() => setActiveTab('terlambat')}
              className={`px-3.5 py-2 rounded-lg text-xs font-semibold whitespace-nowrap transition-all ${
                activeTab === 'terlambat'
                  ? 'bg-amber-600 text-white shadow'
                  : 'text-slate-400 hover:text-white'
              }`}
            >
              Terlambat ({totalTerlambat})
            </button>
            <button
              onClick={() => setActiveTab('belum')}
              className={`px-3.5 py-2 rounded-lg text-xs font-semibold whitespace-nowrap transition-all ${
                activeTab === 'belum'
                  ? 'bg-rose-600 text-white shadow'
                  : 'text-slate-400 hover:text-white'
              }`}
            >
              Belum Absen ({totalBelum})
            </button>
          </div>

          {/* Action Buttons */}
          <div className="flex items-center gap-2 self-end md:self-auto flex-wrap">
            <button
              onClick={() => setShowAddStudentModal(true)}
              className="bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold px-3 py-2 rounded-xl flex items-center gap-1.5 transition-all shadow-sm"
            >
              <UserPlus className="w-4 h-4" />
              Daftar Siswa Baru
            </button>

            <button
              onClick={() => setShowAnalytics(!showAnalytics)}
              className="bg-slate-700 hover:bg-slate-600 text-slate-200 text-xs font-semibold px-3 py-2 rounded-xl flex items-center gap-1.5 transition-all border border-slate-600"
            >
              <BarChart3 className="w-4 h-4 text-indigo-400" />
              {showAnalytics ? 'Sembunyikan Grafik' : 'Lihat Grafik Stats'}
            </button>

            <button
              onClick={() => setShowDbModal(true)}
              className="bg-emerald-600/20 hover:bg-emerald-600/30 text-emerald-300 border border-emerald-500/40 text-xs font-semibold px-3 py-2 rounded-xl flex items-center gap-1.5 transition-all"
            >
              <Database className="w-4 h-4 text-emerald-400" />
              Integrasi MySQL
            </button>
          </div>
        </div>

        {/* Filter Input Search & Kelas */}
        <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
          <div className="relative sm:col-span-2">
            <input
              type="text"
              placeholder="Cari Nama Siswa, NISN, atau Tag RFID (contoh: 2234749610)..."
              value={searchQuery}
              onChange={(e) => setSearchQuery(e.target.value)}
              className="w-full bg-slate-900/80 border border-slate-700 rounded-xl px-3.5 py-2.5 pl-10 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500"
            />
            <Search className="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" />
          </div>

          <div className="relative">
            <select
              value={selectedClass}
              onChange={(e) => setSelectedClass(e.target.value)}
              className="w-full bg-slate-900/80 border border-slate-700 rounded-xl px-3.5 py-2.5 pr-8 text-xs text-white focus:outline-none focus:border-indigo-500 appearance-none"
            >
              {kelasList.map((cls) => (
                <option key={cls} value={cls}>Filter Kelas: {cls}</option>
              ))}
            </select>
            <Filter className="w-4 h-4 text-slate-400 absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none" />
          </div>
        </div>

        {/* Analytics Collapsible Area */}
        {showAnalytics && (
          <div className="bg-slate-900/90 border border-slate-700 rounded-xl p-4 my-2 grid grid-cols-1 lg:grid-cols-3 gap-6 animate-fadeIn">
            <div className="lg:col-span-2">
              <h4 className="text-xs font-bold text-slate-300 uppercase tracking-wider mb-3">
                Grafik Aktivitas Tap Jam Masuk & Pulang
              </h4>
              <div className="h-56">
                <ResponsiveContainer width="100%" height="100%">
                  <BarChart data={HOURLY_ACTIVITY_DATA}>
                    <CartesianGrid strokeDasharray="3 3" stroke="#334155" />
                    <XAxis dataKey="jam" stroke="#94a3b8" fontSize={11} />
                    <YAxis stroke="#94a3b8" fontSize={11} />
                    <Tooltip
                      contentStyle={{ backgroundColor: '#1e293b', borderColor: '#475569', borderRadius: '8px', color: '#fff' }}
                    />
                    <Bar dataKey="masuk" fill="#10B981" name="Jam Masuk" radius={[4, 4, 0, 0]} />
                    <Bar dataKey="pulang" fill="#6366F1" name="Jam Pulang" radius={[4, 4, 0, 0]} />
                  </BarChart>
                </ResponsiveContainer>
              </div>
            </div>

            <div>
              <h4 className="text-xs font-bold text-slate-300 uppercase tracking-wider mb-3">
                Persentase Status Kehadiran
              </h4>
              <div className="h-56">
                <ResponsiveContainer width="100%" height="100%">
                  <PieChart>
                    <Pie
                      data={pieData}
                      cx="50%"
                      cy="50%"
                      innerRadius={45}
                      outerRadius={70}
                      paddingAngle={5}
                      dataKey="value"
                    >
                      {pieData.map((entry, index) => (
                        <Cell key={`cell-${index}`} fill={entry.color} />
                      ))}
                    </Pie>
                    <Tooltip
                      contentStyle={{ backgroundColor: '#1e293b', borderColor: '#475569', borderRadius: '8px', color: '#fff' }}
                    />
                    <Legend wrapperStyle={{ fontSize: '11px', color: '#94a3b8' }} />
                  </PieChart>
                </ResponsiveContainer>
              </div>
            </div>
          </div>
        )}

        <div className="overflow-x-auto rounded-xl border border-slate-700/80">
          <table className="w-full text-left text-xs border-collapse">
            <thead>
              <tr className="bg-slate-900/90 text-slate-300 border-b border-slate-700 font-semibold">
                <th className="py-3.5 px-4">No</th>
                <th className="py-3.5 px-4">Siswa</th>
                <th className="py-3.5 px-4">NISN / Tag RFID USB</th>
                <th className="py-3.5 px-4">Kelas</th>
                <th className="py-3.5 px-4 text-center">Jam Masuk</th>
                <th className="py-3.5 px-4 text-center">Jam Pulang</th>
                <th className="py-3.5 px-4 text-center">Status Absensi</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-700/60 text-slate-300">
              {filteredStudents.length > 0 ? (
                filteredStudents.map((student, idx) => (
                  <tr key={student.id} className="hover:bg-slate-700/30 transition-colors">
                    <td className="py-3.5 px-4 font-mono text-slate-400">{idx + 1}</td>
                    
                    {/* Profil Siswa */}
                    <td className="py-3.5 px-4">
                      <div className="flex items-center gap-3">
                        <img
                          src={student.avatar}
                          alt={student.nama}
                          className="w-9 h-9 rounded-full object-cover border border-slate-600 shrink-0"
                        />
                        <div>
                          <div className="font-semibold text-white text-sm">{student.nama}</div>
                          <div className="text-[11px] text-slate-400">
                            Gender: {student.gender === 'L' ? 'Laki-laki' : 'Perempuan'}
                          </div>
                        </div>
                      </div>
                    </td>

                    {/* NISN & Card RFID */}
                    <td className="py-3.5 px-4 font-mono">
                      <div className="text-slate-200">NISN: {student.nisn}</div>
                      <span className={`inline-block text-[10px] px-2 py-0.5 rounded border mt-1 font-semibold ${
                        student.rfidTag === '2234749610'
                          ? 'bg-indigo-950 text-indigo-300 border-indigo-500/50'
                          : 'bg-slate-900 text-slate-400 border-slate-700'
                      }`}>
                        RFID: {student.rfidTag}
                      </span>
                    </td>

                    {/* Kelas */}
                    <td className="py-3.5 px-4 font-medium text-slate-300">
                      <span className="bg-slate-700/60 px-2.5 py-1 rounded-md border border-slate-600/80">
                        {student.kelas}
                      </span>
                    </td>

                    {/* Jam Masuk */}
                    <td className="py-3.5 px-4 text-center">
                      {student.jamMasuk !== '-' ? (
                        <div>
                          <span className="font-mono text-emerald-400 font-semibold text-xs">
                            {student.jamMasuk}
                          </span>
                          <div>
                            <span className={`text-[10px] px-2 py-0.5 rounded-full mt-1 inline-block ${
                              student.statusMasuk === 'Terlambat'
                                ? 'bg-amber-500/20 text-amber-300 border border-amber-500/30'
                                : 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30'
                            }`}>
                              {student.statusMasuk}
                            </span>
                          </div>
                        </div>
                      ) : (
                        <span className="text-slate-500 font-mono">-</span>
                      )}
                    </td>

                    {/* Jam Pulang */}
                    <td className="py-3.5 px-4 text-center">
                      {student.jamPulang !== '-' ? (
                        <div>
                          <span className="font-mono text-indigo-400 font-semibold text-xs">
                            {student.jamPulang}
                          </span>
                          <div>
                            <span className="text-[10px] bg-indigo-500/20 text-indigo-300 border border-indigo-500/30 px-2 py-0.5 rounded-full mt-1 inline-block">
                              {student.statusPulang}
                            </span>
                          </div>
                        </div>
                      ) : (
                        <span className="text-slate-500 font-mono">-</span>
                      )}
                    </td>

                    {/* Status Hari Ini */}
                    <td className="py-3.5 px-4 text-center">
                      {student.statusHariIni === 'Pulang' && (
                        <span className="inline-flex items-center gap-1 bg-indigo-500/20 text-indigo-300 border border-indigo-500/40 px-2.5 py-1 rounded-full text-[11px] font-semibold">
                          <CheckCircle2 className="w-3.5 h-3.5 text-indigo-400" /> Selesai Pulang
                        </span>
                      )}
                      {student.statusHariIni === 'Masuk' && (
                        <span className="inline-flex items-center gap-1 bg-emerald-500/20 text-emerald-300 border border-emerald-500/40 px-2.5 py-1 rounded-full text-[11px] font-semibold">
                          <LogIn className="w-3.5 h-3.5 text-emerald-400" /> Di Sekolah
                        </span>
                      )}
                      {student.statusHariIni === 'Belum Absen' && (
                        <span className="inline-flex items-center gap-1 bg-rose-500/20 text-rose-300 border border-rose-500/40 px-2.5 py-1 rounded-full text-[11px] font-semibold">
                          <XCircle className="w-3.5 h-3.5 text-rose-400" /> Belum Absen
                        </span>
                      )}
                    </td>
                  </tr>
                ))
              ) : (
                <tr>
                  <td colSpan={7} className="py-8 text-center text-slate-500">
                    Tidak ada data siswa yang cocok dengan kriteria pencarian.
                  </td>
                </tr>
              )}
            </tbody>
          </table>
        </div>
      </div>

      {showDbModal && (
        <div className="fixed inset-0 bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4 z-50 animate-fadeIn">
          <div className="bg-slate-800 border border-slate-700 rounded-2xl max-w-3xl w-full max-h-[90vh] overflow-y-auto p-6 shadow-2xl flex flex-col gap-5">
            
            <div className="flex items-center justify-between border-b border-slate-700 pb-3">
              <div className="flex items-center gap-2 text-emerald-400">
                <Database className="w-6 h-6" />
                <h3 className="text-lg font-bold text-white">Panduan Integrasi Database MySQL & API REST</h3>
              </div>
              <button
                onClick={() => setShowDbModal(false)}
                className="text-slate-400 hover:text-white p-1 rounded-lg bg-slate-700/50"
              >
                <X className="w-5 h-5" />
              </button>
            </div>

            <div className="space-y-4 text-xs text-slate-300">
              <div>
                <h4 className="text-sm font-semibold text-indigo-300 mb-1 flex items-center gap-1.5">
                  <Code className="w-4 h-4" /> 1. Struktur Database MySQL (`tb_siswa` & `tb_absensi`)
                </h4>
                <p className="text-slate-400 mb-2">
                  Jalankan SQL Query berikut di phpMyAdmin / MySQL Workbench Anda untuk menyiapkan tabel siswa & absensi:
                </p>
                <pre className="bg-slate-900 p-3 rounded-xl border border-slate-700 text-indigo-300 font-mono text-[11px] overflow-x-auto">
{`CREATE DATABASE db_absensi_rfid;
USE db_absensi_rfid;

-- Tabel Master Siswa
CREATE TABLE tb_siswa (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nisn VARCHAR(20) UNIQUE NOT NULL,
    rfid_tag VARCHAR(50) UNIQUE NOT NULL,
    nama VARCHAR(100) NOT NULL,
    kelas VARCHAR(20) NOT NULL,
    gender ENUM('L', 'P') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabel Transaksi Absensi
CREATE TABLE tb_absensi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nisn VARCHAR(20) NOT NULL,
    tanggal DATE NOT NULL,
    jam_masuk TIME DEFAULT NULL,
    jam_pulang TIME DEFAULT NULL,
    status_masuk ENUM('Tepat Waktu', 'Terlambat') DEFAULT 'Tepat Waktu',
    status_pulang ENUM('Belum', 'Selesai') DEFAULT 'Belum',
    FOREIGN KEY (nisn) REFERENCES tb_siswa(nisn) ON DELETE CASCADE
);`}
                </pre>
              </div>

              <div>
                <h4 className="text-sm font-semibold text-indigo-300 mb-1 flex items-center gap-1.5">
                  <Usb className="w-4 h-4" /> 2. Cara Kerja RFID USB Reader (HID Mode Keyboard)
                </h4>
                <p className="text-slate-400 leading-relaxed">
                  RFID Reader berbasis USB bertindak sebagai <strong>HID Keyboard Emulator</strong>. Saat kartu ditempelkan, alat ini otomatis mengetikkan string nomor kartu ID (misal: <code>2234749610</code>) dan langsung menekan tombol <strong>Enter</strong>.
                </p>
              </div>
            </div>

            <div className="pt-3 border-t border-slate-700 flex justify-end">
              <button
                onClick={() => setShowDbModal(false)}
                className="bg-indigo-600 hover:bg-indigo-500 text-white font-semibold text-xs px-5 py-2.5 rounded-xl transition-all"
              >
                Tutup Panduan
              </button>
            </div>

          </div>
        </div>
      )}

      {showAddStudentModal && (
        <div className="fixed inset-0 bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4 z-50 animate-fadeIn">
          <div className="bg-slate-800 border border-slate-700 rounded-2xl max-w-md w-full p-6 shadow-2xl flex flex-col gap-4">
            
            <div className="flex items-center justify-between border-b border-slate-700 pb-3">
              <div className="flex items-center gap-2 text-indigo-400">
                <UserPlus className="w-5 h-5" />
                <h3 className="text-base font-bold text-white">Registrasi Kartu RFID Siswa Baru</h3>
              </div>
              <button
                onClick={() => setShowAddStudentModal(false)}
                className="text-slate-400 hover:text-white p-1 rounded-lg bg-slate-700/50"
              >
                <X className="w-5 h-5" />
              </button>
            </div>

            <form onSubmit={handleAddStudent} className="space-y-3 text-xs">
              <div>
                <label className="block text-slate-300 font-medium mb-1">Nama Lengkap Siswa</label>
                <input
                  type="text"
                  required
                  placeholder="Contoh: Muhammad Rizky"
                  value={newStudent.nama}
                  onChange={(e) => setNewStudent({ ...newStudent, nama: e.target.value })}
                  className="w-full bg-slate-900 border border-slate-700 rounded-lg px-3 py-2 text-white focus:outline-none focus:border-indigo-500"
                />
              </div>

              <div>
                <label className="block text-slate-300 font-medium mb-1">Nomor NISN</label>
                <input
                  type="text"
                  required
                  placeholder="Contoh: 0082234749"
                  value={newStudent.nisn}
                  onChange={(e) => setNewStudent({ ...newStudent, nisn: e.target.value })}
                  className="w-full bg-slate-900 border border-slate-700 rounded-lg px-3 py-2 text-white font-mono focus:outline-none focus:border-indigo-500"
                />
              </div>

              <div>
                <label className="block text-slate-300 font-medium mb-1">
                  Tag Code RFID (Tempel Kartu pada USB Reader)
                </label>
                <input
                  type="text"
                  required
                  placeholder="Contoh: 2234749610"
                  value={newStudent.rfidTag}
                  onChange={(e) => setNewStudent({ ...newStudent, rfidTag: e.target.value })}
                  className="w-full bg-slate-900 border border-indigo-500/60 rounded-lg px-3 py-2 text-indigo-300 font-mono font-bold focus:outline-none focus:border-indigo-400"
                />
              </div>

              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="block text-slate-300 font-medium mb-1">Kelas</label>
                  <select
                    value={newStudent.kelas}
                    onChange={(e) => setNewStudent({ ...newStudent, kelas: e.target.value })}
                    className="w-full bg-slate-900 border border-slate-700 rounded-lg px-3 py-2 text-white focus:outline-none focus:border-indigo-500"
                  >
                    <option value="XII RPL 1">XII RPL 1</option>
                    <option value="XII RPL 2">XII RPL 2</option>
                    <option value="XI TKJ 2">XI TKJ 2</option>
                    <option value="X AKL 3">X AKL 3</option>
                    <option value="X IPA 1">X IPA 1</option>
                  </select>
                </div>

                <div>
                  <label className="block text-slate-300 font-medium mb-1">Jenis Kelamin</label>
                  <select
                    value={newStudent.gender}
                    onChange={(e) => setNewStudent({ ...newStudent, gender: e.target.value })}
                    className="w-full bg-slate-900 border border-slate-700 rounded-lg px-3 py-2 text-white focus:outline-none focus:border-indigo-500"
                  >
                    <option value="L">Laki-laki (L)</option>
                    <option value="P">Perempuan (P)</option>
                  </select>
                </div>
              </div>

              <div className="pt-3 border-t border-slate-700 flex justify-end gap-2">
                <button
                  type="button"
                  onClick={() => setShowAddStudentModal(false)}
                  className="px-4 py-2 rounded-lg bg-slate-700 text-slate-300 font-medium hover:bg-slate-600"
                >
                  Batal
                </button>
                <button
                  type="submit"
                  className="px-4 py-2 rounded-lg bg-indigo-600 text-white font-medium hover:bg-indigo-500 flex items-center gap-1.5"
                >
                  <Check className="w-4 h-4" /> Simpan Siswa
                </button>
              </div>
            </form>

          </div>
        </div>
      )}

      <footer className="text-center text-xs text-slate-500 py-2 flex flex-col sm:flex-row items-center justify-between border-t border-slate-800 gap-2">
        <div>SMK Smart School - Kios Sistem Absensi RFID USB Hardware Mode</div>
        <div className="flex items-center gap-2">
          <span className="w-2 h-2 rounded-full bg-emerald-400"></span>
          <span>Siap Terkoneksi Database MySQL</span>
        </div>
      </footer>

    </div>
  );
}