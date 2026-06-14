import React, { useState, useEffect, useRef } from "react";
import JSZip from "jszip";
import { 
  BankCard, 
  Transaction, 
  AuditLog, 
  GatewaySettings 
} from "./types";
import { 
  Layout, 
  Settings as SettingsIcon, 
  CreditCard, 
  FileText, 
  Database, 
  Download, 
  Eye, 
  Check, 
  X, 
  Search, 
  Trash2, 
  Smartphone, 
  FolderSync, 
  RefreshCw, 
  Clock, 
  CloudRain, 
  AlertCircle, 
  Menu,
  ChevronDown,
  Info,
  Calendar,
  Layers,
  Upload,
  User,
  ExternalLink
} from "lucide-react";
import { motion, AnimatePresence } from "motion/react";

// آیکون بانک‌های محبوب ایران جهت ظاهر فوق‌العاده شکیل
const BANK_DESIGNS: Record<string, { bg: string; logoText: string }> = {
  "بانک ملی ایران": { bg: "linear-gradient(135deg, #1e3a8a, #3b82f6)", logoText: "ملی" },
  "بانک ملت": { bg: "linear-gradient(135deg, #b91c1c, #ec4899)", logoText: "ملت" },
  "بانک تجارت": { bg: "linear-gradient(135deg, #0d9488, #14b8a6)", logoText: "تجارت" },
  "بانک صادرات": { bg: "linear-gradient(135deg, #0f172a, #475569)", logoText: "صادرات" },
  "بانک پاسارگاد": { bg: "linear-gradient(135deg, #d97706, #fbbf24)", logoText: "پاسارگارد" },
  "پیش‌فرض": { bg: "linear-gradient(135deg, #4f46e5, #06b6d4)", logoText: "بانک" }
};

export default function App() {
  // --- حالت‌های پیش‌فرض مدیر و پایگاه داده شبیه‌ساز ---
  const [cards, setCards] = useState<BankCard[]>([
    {
      id: 1,
      card_number: "6037996711223344",
      bank_name: "بانک ملی ایران",
      holder_name: "ابوالفضل محمدی",
      active: true,
      created_at: "2026-06-14T10:00:00Z"
    },
    {
      id: 2,
      card_number: "6104337777889900",
      bank_name: "بانک ملت",
      holder_name: "زهرا سادات مرعشی",
      active: true,
      created_at: "2026-06-14T10:15:00Z"
    }
  ]);

  const [transactions, setTransactions] = useState<Transaction[]>([
    {
      id: 1001,
      order_id: 34120,
      user_id: 12,
      full_name: "محمدرضا حسینی",
      mobile: "09121112233",
      amount: 450000,
      bank_card_id: 1,
      last4digits: "4321",
      receipt_url: "https://chart.googleapis.com/chart?chs=200x200&cht=qr&chl=p2p_receipt_mock",
      status: "pending",
      admin_notes: "بابت خرید شابلون وب‌سایت شخصی و قالب المنتور پلاس.",
      ip_address: "185.143.12.8",
      created_at: "2026-06-14T10:10:00Z"
    },
    {
      id: 1002,
      order_id: 34121,
      user_id: 15,
      full_name: "امین دهقانی",
      mobile: "09356667788",
      amount: 1250000,
      bank_card_id: 2,
      last4digits: "9876",
      receipt_url: null,
      status: "approved",
      admin_notes: "پرداخت تأیید شد و انبارداری تأییدیه برداشت کالا صادر کرد.",
      ip_address: "5.123.82.164",
      created_at: "2026-06-14T10:18:00Z"
    }
  ]);

  const [logs, setLogs] = useState<AuditLog[]>([
    {
      id: 1,
      event: "INSTALLATION",
      message: "پایگاه داده افزونه با موفقیت نصب و جدول wp_c2c_transactions ایجاد شد.",
      ip_address: "127.0.0.1",
      user_id: 1,
      created_at: "2026-06-14T10:00:00Z"
    }
  ]);

  const [settings, setSettings] = useState<GatewaySettings>({
    enable_receipt: true,
    require_last4: "required",
    enable_sms: true,
    sms_api_key: "KAVENEGAR_MOCK_API_KEY_9812",
    sms_sender: "10006363",
    sms_admin_mobile: "09121234567",
    enable_telegram: true,
    telegram_bot_token: "654321098:AAGHyt_dfgK90klm",
    telegram_chat_id: "98765432",
    recaptcha_site_key: "MOCK_SITE_KEY",
    recaptcha_secret_key: "MOCK_SECRET_KEY",
    theme: "glassmorphism",
    email_template: "با سلام؛ پرداخت کارت به کارت ثبت شد.\nنام خریدار: {full_name}\nموبایل: {mobile}\nمبلغ: {amount} تومان\nسفارش: {order_id}",
    delete_tables_on_uninstall: false
  });

  // --- مدیریت منوهای بخش ادمین وردپرس ---
  const [activeAdminTab, setActiveAdminTab] = useState<"transactions" | "cards" | "settings" | "logs" | "export">("transactions");
  
  // --- فیلترهای تراکنش ---
  const [txSearch, setTxSearch] = useState("");
  const [txStatusFilter, setTxStatusFilter] = useState("");

  // --- فرم افزودن فرضی کارت بانکی ---
  const [newCardNumber, setNewCardNumber] = useState("");
  const [newCardBank, setNewCardBank] = useState("");
  const [newCardHolder, setNewCardHolder] = useState("");

  // --- جزئیات مودال یادداشت تراکنش ادمین ---
  const [selectedTxForNote, setSelectedTxForNote] = useState<Transaction | null>(null);
  const [modalAdminNotes, setModalAdminNotes] = useState("");

  // --- دانلود کامپایل شده ---
  const [isZipping, setIsZipping] = useState(false);

  // --- حالت‌های فرانت‌اند مشتری (WooCommerce Checkout Simulator) ---
  const [customerFormTab, setCustomerFormTab] = useState<"checkout" | "shortcode">("checkout");
  const [checkoutStep, setCheckoutStep] = useState<"form" | "success">("form");
  const [lastSubmittedTx, setLastSubmittedTx] = useState<Transaction | null>(null);

  // فیلدهای فرم پرداخت مشتری
  const [custName, setCustName] = useState("");
  const [custMobile, setCustMobile] = useState("");
  const [custSelectedCard, setCustSelectedCard] = useState<number>(1);
  const [custLast4, setCustLast4] = useState("");
  const [custReceipt, setCustReceipt] = useState<string | null>(null);
  const [custReceiptName, setCustReceiptName] = useState("");
  const [custRecaptcha, setCustRecaptcha] = useState(false);
  const [formError, setFormError] = useState("");

  // --- سیستم یکپارچه لاگ‌نویسی ادمین ---
  const addLog = (event: string, message: string) => {
    const newLog: AuditLog = {
      id: logs.length + 1,
      event,
      message,
      ip_address: "185.123.45.6",
      user_id: 1,
      created_at: new Date().toISOString()
    };
    setLogs((prev) => [newLog, ...prev]);
  };

  // --- عملیات ادمین روی تراکنش‌ها ---
  const handleUpdateTxStatus = (txId: number, newStatus: "approved" | "rejected" | "pending") => {
    setTransactions((prev) => 
      prev.map((t) => {
        if (t.id === txId) {
          const statusText = newStatus === "approved" ? "تأیید" : (newStatus === "rejected" ? "رد" : "انتظار مجدد");
          addLog("TRANSACTION_STATUS_UPDATE", `تراکنش شماره #${txId} مربوط به خریدار "${t.full_name}" به وضعیت [${statusText}] تغییر یافت.`);
          return { ...t, status: newStatus };
        }
        return t;
      })
    );
  };

  const handleSaveModalNotes = () => {
    if (!selectedTxForNote) return;
    setTransactions((prev) => 
      prev.map((t) => {
        if (t.id === selectedTxForNote.id) {
          addLog("TRANSACTION_NOTE", `یادداشت مدیریتی برای تراکنش #${t.id} ثبت شد.`);
          return { ...t, admin_notes: modalAdminNotes };
        }
        return t;
      })
    );
    setSelectedTxForNote(null);
  };

  // --- کارهای مدیریت کارت‌های بانکی پذیرنده ---
  const handleAddCardSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (!newCardBank || !newCardHolder || newCardNumber.length < 16) {
      alert("لطفا اطلاعات کارت را به طور کامل همراه با ۱۶ رقم کارت وارد کنید.");
      return;
    }
    const newCard: BankCard = {
      id: cards.length + 1,
      card_number: newCardNumber,
      bank_name: newCardBank,
      holder_name: newCardHolder,
      active: true,
      created_at: new Date().toISOString()
    };
    setCards((prev) => [newCard, ...prev]);
    addLog("CARD_ADDED", `کارت جدید شماره ${newCardNumber} (${newCardBank} به نام ${newCardHolder}) اضافه گردید.`);
    setNewCardNumber("");
    setNewCardBank("");
    setNewCardHolder("");
  };

  const handleToggleCardActive = (cardId: number) => {
    setCards((prev) => 
      prev.map((c) => {
        if (c.id === cardId) {
          addLog("CARD_TOGGLE", `حالت فعال‌ بودن کارت شماره ${c.card_number} تغییر کرد.`);
          return { ...c, active: !c.active };
        }
        return c;
      })
    );
  };

  const handleDeleteCard = (cardId: number) => {
    const cardToDelete = cards.find((c) => c.id === cardId);
    if (!cardToDelete) return;
    if (confirm(`آیا از حذف کارت بانک ${cardToDelete.bank_name} اطمینان دارید؟`)) {
      setCards((prev) => prev.filter((c) => c.id !== cardId));
      addLog("CARD_DELETED", `کارت بانکی ${cardToDelete.card_number} حذف فیزیکی شد.`);
    }
  };

  // --- بهینه سازی تم تنظیمات ---
  const handleSettingsFormChange = (newSettings: Partial<GatewaySettings>) => {
    setSettings((prev) => {
      const next = { ...prev, ...newSettings };
      return next;
    });
  };

  // --- آپلود شبیه‌ساز فایل رسید مشتری ---
  const handleFileDrop = (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0];
    if (!file) return;
    
    // ولیدیشن حجم (باید زیر ۲ مگابایت باشد)
    if (file.size > 2 * 1024 * 1024) {
      setFormError("خطا: حجم تصویر فیش ارسالی نباید بیش از ۲ مگابایت باشد.");
      return;
    }
    setFormError("");
    setCustReceiptName(file.name);

    const reader = new FileReader();
    reader.onload = (event) => {
      if (typeof event.target?.result === "string") {
        setCustReceipt(event.target.result);
      }
    };
    reader.readAsDataURL(file);
  };

  // --- پردازش و خرید مشتری ---
  const handleCheckoutSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (!custName.trim()) {
      setFormError("لطفا نام و نام خانوادگی کامل را واریز کننده را ثبت کنید.");
      return;
    }
    if (!custMobile.trim() || !custMobile.match(/^(09|9)[0-9]{9}$/)) {
      setFormError("شماره موبایل اشتباه است. باید فرمت معتبر ده‌رقمی یا یازده‌رقمی مثل 09121234567 باشد.");
      return;
    }
    if (settings.require_last4 === "required" && (!custLast4 || custLast4.length !== 4)) {
      setFormError("وارد کردن دقیق ۴ رقم آخر کارت فرستنده اجباری است.");
      return;
    }

    setFormError("");

    // درج تراکنش جدید
    const activeBank = cards.find(c => c.id === custSelectedCard);
    const newTx: Transaction = {
      id: transactions.length + 1001,
      order_id: customerFormTab === "checkout" ? Math.floor(10000 + Math.random() * 90000) : null,
      user_id: 19,
      full_name: custName,
      mobile: custMobile,
      amount: customerFormTab === "checkout" ? 640000 : 150000,
      bank_card_id: custSelectedCard,
      last4digits: custLast4,
      receipt_url: custReceipt, // آدرس دیتای شیشه‌ای جهت پیش‌نمایش ادمین
      status: "pending",
      admin_notes: "",
      ip_address: "185.123.45.6",
      created_at: new Date().toISOString()
    };

    setTransactions((prev) => [newTx, ...prev]);
    setLastSubmittedTx(newTx);
    addLog(
      "TRANSACTION_REGISTERED", 
      `یک سند پرداخت به مبلغ ${newTx.amount.toLocaleString()} تومان توسط مشتری "${newTx.full_name}" ثبت شد.`
    );

    // انتقال به صفحه موفقیت
    setCheckoutStep("success");
  };

  const handleResetCheckoutForm = () => {
    setCustName("");
    setCustMobile("");
    setCustLast4("");
    setCustReceipt(null);
    setCustReceiptName("");
    setCheckoutStep("form");
    setCustRecaptcha(false);
    setFormError("");
  };

  // --- مکانیزم حیرت‌انگیز دانلود مستقیم ZIP افزونه اصلی ---
  const handleDownloadZip = async () => {
    setIsZipping(true);
    try {
      const response = await fetch("/api/files");
      if (!response.ok) {
        throw new Error("خطا در دریافت کدهای افزونه از وب‌سرویس بک‌اند پروژه.");
      }
      const data = await response.json();
      if (!data.files) {
        throw new Error("پکیج کدهای افزونه به طور معتبر یافت نشد.");
      }

      const zip = new JSZip();
      
      // ساخت پوشه روت افزونه و تزریق تک‌ستک فایل‌ها
      const mainFolder = zip.folder("professional-card-to-card");
      if (mainFolder) {
        Object.entries(data.files).forEach(([relPath, content]) => {
          const normalizedPath = relPath.replace(/\\/g, '/');
          mainFolder.file(normalizedPath, content as string);
        });
      }

      // تولید فایل باینری فشرده با فناوری استاندارد فشرده‌سازی DEFLATE مناسب با وب‌سرورهای آپاچی و لایت‌اسپید
      const content = await zip.generateAsync({ 
        type: "blob",
        compression: "DEFLATE",
        compressionOptions: { level: 9 }
      });
      const link = document.createElement("a");
      link.href = URL.createObjectURL(content);
      link.download = "professional-card-to-card.zip";
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);

      addLog("PLUGIN_EXPORTED", "بسته نصبی افزونه ووکامرس (فایل ZIP) با موفقیت تولید و دانلود شد.");
    } catch (err: any) {
      alert("خطا در دانلود افزونه: " + err.message);
    } finally {
      setIsZipping(false);
    }
  };

  // فیلتر کردن تراکنش‌های جدول ادمین
  const filteredTxs = transactions.filter((t) => {
    const matchesSearch = 
      t.full_name.toLowerCase().includes(txSearch.toLowerCase()) ||
      t.mobile.includes(txSearch) ||
      t.id.toString().includes(txSearch) ||
      (t.order_id && t.order_id.toString().includes(txSearch));
    
    const matchesStatus = txStatusFilter === "" || t.status === txStatusFilter;
    
    return matchesSearch && matchesStatus;
  });

  return (
    <div className="min-h-screen bg-slate-900 text-slate-100 font-sans flex flex-col antialiased">
      
      {/* هدر وب‌سایت شبیه‌ساز */}
      <header className="border-b border-slate-800 bg-slate-950 px-6 py-4 flex flex-col md:flex-row justify-between items-center gap-4 z-10 shadow-lg">
        <div className="flex items-center gap-3">
          <div className="bg-blue-600 p-2.5 rounded-xl shadow-md shadow-blue-900/30">
            <Layers className="h-6 w-6 text-white" />
          </div>
          <div>
            <h1 className="text-xl font-bold tracking-tight text-white flex items-center gap-2">
              شبیه‌ساز درگاه پرداخت کارت به کارت ووکامرس
              <span className="text-xs bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 px-2.5 py-0.5 rounded-full font-mono font-medium">پایدار 1.0.0</span>
            </h1>
            <p className="text-xs text-slate-400 mt-0.5">درگاه پرداخت دستی کارت به کارت به همراه مدیریت پیشرفته و قالب فوق‌العاده شکیل شیشه‌ای (فارسی)</p>
          </div>
        </div>
        <div className="flex items-center gap-3 w-full md:w-auto justify-end">
          <button 
            onClick={handleDownloadZip}
            disabled={isZipping}
            className="w-full md:w-auto inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-blue-600 hover:bg-blue-500 active:bg-blue-700 disabled:bg-blue-800 text-white font-semibold rounded-xl text-sm transition shadow-lg shadow-blue-950/40 cursor-pointer"
          >
            {isZipping ? (
              <>
                <RefreshCw className="h-4 w-4 animate-spin" />
                در حال بسته‌بندی زیپ...
              </>
            ) : (
              <>
                <Download className="h-4 w-4" />
                دانلود افزونه اصلی (فایل ZIP)
              </>
            )}
          </button>
        </div>
      </header>

      {/* بخش اصلی با تقسیم‌بندی لوکس */}
      <main className="flex-1 grid grid-cols-1 xl:grid-cols-12 overflow-hidden h-full">
        
        {/* ======================================================== */}
        {/* پنل چپ: پیشخوان مدیریت وردپرس (WordPress Admin Dashboard) */}
        {/* ======================================================== */}
        <section className="col-span-1 xl:col-span-7 border-l border-slate-800 flex flex-col bg-slate-950 overflow-y-auto">
          
          {/* هدر کوچک شلوغ وردپرس فارسی */}
          <div className="bg-slate-900 px-5 py-3 border-b border-slate-800 flex items-center justify-between text-xs text-slate-400">
            <div className="flex items-center gap-4">
              <span className="font-bold text-slate-200">پیشخوان مدیریت وردپرس فارسی</span>
              <span className="h-4 w-px bg-slate-800"></span>
              <span className="flex items-center gap-1"><Smartphone className="h-3 w-3" /> ووکامرس فعال است</span>
            </div>
            <div className="flex items-center gap-2">
              <span className="bg-emerald-500 w-2 h-2 rounded-full animate-pulse"></span>
              <span className="text-[11px] font-mono">DB: Local Mock</span>
            </div>
          </div>

          <div className="flex-1 flex flex-col md:flex-row min-h-[550px]">
            {/* سایدبار وردپرس با طرح کلاسیک تیره */}
            <nav className="w-full md:w-56 bg-slate-950 border-b md:border-b-0 md:border-l border-slate-800 p-3 flex flex-col gap-1.5 shrink-0">
              <div className="px-3 py-2 text-[11px] font-bold text-slate-500 uppercase tracking-wider">شتاب‌بانک کارت به کارت</div>
              
              <button 
                onClick={() => setActiveAdminTab("transactions")}
                className={`w-full inline-flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition cursor-pointer ${activeAdminTab === "transactions" ? "bg-blue-600/10 text-blue-400 border border-blue-500/20" : "text-slate-400 hover:text-slate-200 hover:bg-slate-900"}`}
              >
                <FileText className="h-4 w-4" />
                تراکنش‌های دریافتی
                {transactions.filter(t => t.status === "pending").length > 0 && (
                  <span className="mr-auto bg-amber-500 text-slate-950 font-bold px-1.5 py-0.5 text-xs rounded-full">
                    {transactions.filter(t => t.status === "pending").length}
                  </span>
                )}
              </button>

              <button 
                onClick={() => setActiveAdminTab("cards")}
                className={`w-full inline-flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition cursor-pointer ${activeAdminTab === "cards" ? "bg-blue-600/10 text-blue-400 border border-blue-500/20" : "text-slate-400 hover:text-slate-200 hover:bg-slate-900"}`}
              >
                <CreditCard className="h-4 w-4" />
                کارت‌های پذیرنده
              </button>

              <button 
                onClick={() => setActiveAdminTab("settings")}
                className={`w-full inline-flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition cursor-pointer ${activeAdminTab === "settings" ? "bg-blue-600/10 text-blue-400 border border-blue-500/20" : "text-slate-400 hover:text-slate-200 hover:bg-slate-900"}`}
              >
                <SettingsIcon className="h-4 w-4" />
                تنظیمات درگاه
              </button>

              <button 
                onClick={() => setActiveAdminTab("logs")}
                className={`w-full inline-flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition cursor-pointer ${activeAdminTab === "logs" ? "bg-blue-600/10 text-blue-400 border border-blue-500/20" : "text-slate-400 hover:text-slate-200 hover:bg-slate-900"}`}
              >
                <Database className="h-4 w-4" />
                لاگ‌های سیستمی
              </button>

              <button 
                onClick={() => setActiveAdminTab("export")}
                className={`w-full inline-flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition cursor-pointer ${activeAdminTab === "export" ? "bg-blue-600/10 text-blue-400 border border-blue-500/20" : "text-slate-400 hover:text-slate-200 hover:bg-slate-900"}`}
              >
                <Layout className="h-4 w-4" />
                خروجی گزارشگیری
              </button>
            </nav>

            {/* بدنه محتوای ادمین */}
            <div className="flex-1 p-6 overflow-y-auto">
              
              {/* تب مدیریت تراکنش‌ها */}
              {activeAdminTab === "transactions" && (
                <div className="space-y-6">
                  <div className="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                    <div>
                      <h2 className="text-lg font-bold text-white">تراکنش‌های ثبت شده (صندوق فروش)</h2>
                      <p className="text-xs text-slate-400 mt-1">لیست تمام درخواست‌های واریز فیش دستی ثبت شده در ووکامرس</p>
                    </div>
                  </div>

                  {/* پنل فیلتر و جستجو */}
                  <div className="grid grid-cols-1 md:grid-cols-2 gap-3 bg-slate-900/50 p-4 rounded-xl border border-slate-800">
                    <div className="relative">
                      <Search className="absolute right-3 top-3 h-4 w-4 text-slate-500" />
                      <input 
                        type="text" 
                        value={txSearch}
                        onChange={(e) => setTxSearch(e.target.value)}
                        placeholder="جستجو بر اساس نام، موبایل، کد سفارش..."
                        className="w-full pl-3 pr-10 py-2 bg-slate-950 border border-slate-800 rounded-lg text-sm focus:outline-none focus:border-blue-500 text-slate-200"
                      />
                    </div>
                    <div>
                      <select 
                        value={txStatusFilter}
                        onChange={(e) => setTxStatusFilter(e.target.value)}
                        className="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-lg text-sm text-slate-300 focus:outline-none focus:border-blue-500"
                      >
                        <option value="">همه وضعیت‌ها</option>
                        <option value="pending">در انتظار بررسی</option>
                        <option value="approved">تایید شده</option>
                        <option value="rejected">رد شده</option>
                      </select>
                    </div>
                  </div>

                  {/* جدول عریض تراکنش‌ها */}
                  <div className="overflow-x-auto border border-slate-800 rounded-xl bg-slate-950">
                    <table className="w-full text-right text-sm border-collapse">
                      <thead>
                        <tr className="border-b border-slate-800 bg-slate-900/40 text-slate-400 text-xs font-semibold">
                          <th className="p-4">سفارش/کد</th>
                          <th className="p-4">واریز کننده</th>
                          <th className="p-4">اطلاعات پرداختی</th>
                          <th className="p-4">مبلغ</th>
                          <th className="p-4 text-center">رسید</th>
                          <th className="p-4 text-center">وضعیت</th>
                          <th className="p-4 text-center">عملیات</th>
                        </tr>
                      </thead>
                      <tbody className="divide-y divide-slate-800">
                        {filteredTxs.length === 0 ? (
                          <tr>
                            <td colSpan={7} className="text-center p-8 text-slate-500">هیچ پرداخت یا سند تراکنشی یافت نشد.</td>
                          </tr>
                        ) : (
                          filteredTxs.map((t) => (
                            <tr key={t.id} className="hover:bg-slate-900/30 text-slate-300 transition">
                              <td className="p-4 whitespace-nowrap">
                                <span className="font-mono text-xs text-slate-500">#{t.id}</span>
                                {t.order_id && (
                                  <div className="text-xs text-blue-500 font-semibold mt-0.5">سفارش #{t.order_id}</div>
                                )}
                              </td>
                              <td className="p-4 font-semibold text-white">
                                {t.full_name}
                                <div className="text-xs text-slate-400 font-mono mt-0.5">{t.mobile}</div>
                              </td>
                              <td className="p-4">
                                <span className="text-xs bg-slate-800 px-2 py-1 rounded text-slate-300 font-mono">
                                  ۴ رقم: {t.last4digits || "---"}
                                </span>
                              </td>
                              <td className="p-4 font-bold text-emerald-400 whitespace-nowrap">
                                {t.amount.toLocaleString()} <span className="text-[10px] text-slate-400 font-normal">تومان</span>
                              </td>
                              <td className="p-4 text-center">
                                {t.receipt_url ? (
                                  <button 
                                    onClick={() => {
                                      // باز کردن رسید در یک صفحه مجزا یا الرت زیبا
                                      const w = window.open();
                                      if (w) {
                                        w.document.write(`<img src="${t.receipt_url}" style="max-width:100%" />`);
                                      }
                                    }}
                                    className="p-1 px-2.5 bg-slate-800 hover:bg-slate-700 hover:text-white rounded text-xs transition cursor-pointer inline-flex items-center gap-1 text-slate-400"
                                  >
                                    <Eye className="h-3 w-3" />
                                    مشاهده fیش
                                  </button>
                                ) : (
                                  <span className="text-xs text-slate-600">فاقد رسید</span>
                                )}
                              </td>
                              <td className="p-4 text-center whitespace-nowrap">
                                {t.status === "approved" && (
                                  <span className="bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 px-2 py-0.5 rounded-full text-xs font-semibold">تأیید شده</span>
                                )}
                                {t.status === "rejected" && (
                                  <span className="bg-rose-500/10 text-rose-400 border border-rose-500/20 px-2 py-0.5 rounded-full text-xs font-semibold">رد شده</span>
                                )}
                                {t.status === "pending" && (
                                  <span className="bg-amber-500/10 text-amber-400 border border-amber-500/20 px-2 py-0.5 rounded-full text-xs font-semibold">در انتظار بررسی</span>
                                )}
                              </td>
                              <td className="p-4 text-center whitespace-nowrap">
                                <div className="inline-flex gap-1.5 justify-center">
                                  {t.status !== "approved" && (
                                    <button 
                                      onClick={() => handleUpdateTxStatus(t.id, "approved")}
                                      className="p-1.5 bg-emerald-600/20 hover:bg-emerald-600 hover:text-slate-950 text-emerald-500 rounded-lg b-none cursor-pointer transition"
                                      title="تایید و تغییر سفارش به پردازش"
                                    >
                                      <Check className="h-4 w-4" />
                                    </button>
                                  )}
                                  {t.status !== "rejected" && (
                                    <button 
                                      onClick={() => handleUpdateTxStatus(t.id, "rejected")}
                                      className="p-1.5 bg-rose-600/20 hover:bg-rose-600 hover:text-white text-rose-500 rounded-lg b-none cursor-pointer transition"
                                      title="رد و لغو سفارش"
                                    >
                                      <X className="h-4 w-4" />
                                    </button>
                                  )}
                                  <button 
                                    onClick={() => {
                                      setSelectedTxForNote(t);
                                      setModalAdminNotes(t.admin_notes);
                                    }}
                                    className="px-2 py-1 bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs rounded-lg transition"
                                  >
                                    یادداشت
                                  </button>
                                </div>
                              </td>
                            </tr>
                          ))
                        )}
                      </tbody>
                    </table>
                  </div>
                </div>
              )}

              {/* تب حساب‌ها و کارت‌ها */}
              {activeAdminTab === "cards" && (
                <div className="space-y-6">
                  <div>
                    <h2 className="text-lg font-bold text-white">مدیریت کارت‌های بانکی پذیرنده</h2>
                    <p className="text-xs text-slate-400 mt-1">افزودن، غیرفعال یا حذف حساب‌های بانکی که مشتریان جهت کارت به کارت مشاهده خواهند کرد.</p>
                  </div>

                  <div className="grid grid-cols-1 lg:grid-cols-5 gap-6">
                    {/* فرم ثبت کارت */}
                    <div className="lg:col-span-2 bg-slate-900 border border-slate-800 p-5 rounded-xl space-y-4 h-fit">
                      <h3 className="text-sm font-bold text-slate-100 border-b border-slate-800 pb-2">ثبت کارت بانکی جدید</h3>
                      
                      <form onSubmit={handleAddCardSubmit} className="space-y-3">
                        <div className="space-y-1">
                          <label className="text-xs text-slate-400">نام بانک مقصد</label>
                          <input 
                            type="text"
                            required
                            value={newCardBank}
                            onChange={(e) => setNewCardBank(e.target.value)}
                            placeholder="مانند: بانک ملی ایران، ملت، تجارت"
                            className="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-lg text-sm text-slate-200 focus:outline-none focus:border-blue-500"
                          />
                        </div>

                        <div className="space-y-1">
                          <label className="text-xs text-slate-400">نام کامل صاحب حساب (به فارسی)</label>
                          <input 
                            type="text"
                            required
                            value={newCardHolder}
                            onChange={(e) => setNewCardHolder(e.target.value)}
                            placeholder="مانند: ابوالفضل محمدی"
                            className="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-lg text-sm text-slate-200 focus:outline-none focus:border-blue-500"
                          />
                        </div>

                        <div className="space-y-1">
                          <label className="text-xs text-slate-400">شماره ۱۶ رقمی کارت بانکی</label>
                          <input 
                            type="text"
                            maxLength={16}
                            required
                            value={newCardNumber}
                            onChange={(e) => setNewCardNumber(e.target.value.replace(/\D/g, ""))}
                            placeholder="6037996711223344"
                            className="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-lg text-sm text-slate-200 focus:outline-none focus:border-blue-500 text-left font-mono"
                          />
                        </div>

                        <button 
                          type="submit"
                          className="w-full py-2 bg-blue-600 hover:bg-blue-500 active:bg-blue-700 text-white font-semibold text-xs rounded-lg transition mt-4 cursor-pointer"
                        >
                          افزودن حساب به سیستم
                        </button>
                      </form>
                    </div>

                    {/* لیست کارت‌ها با ظاهر کارت فیزیکی برای حس فوق‌العاده شکیل */}
                    <div className="lg:col-span-3 space-y-4">
                      <h3 className="text-sm font-bold text-slate-100">کارت‌های فعال در درگاه</h3>
                      
                      {cards.map((card) => {
                        const bankStyles = BANK_DESIGNS[card.bank_name] || BANK_DESIGNS["پیش‌فرض"];
                        return (
                          <div 
                            key={card.id} 
                            style={{ background: bankStyles.bg }}
                            className="p-5 rounded-2xl text-white shadow-lg relative overflow-hidden group border border-white/10"
                          >
                            <div className="absolute top-0 left-0 w-28 h-28 bg-white/5 rounded-full -translate-x-6 -translate-y-6"></div>
                            
                            <div className="flex justify-between items-start z-10 relative">
                              <div>
                                <span className="text-xs font-bold bg-white/25 px-2.5 py-0.5 rounded-full">{card.bank_name}</span>
                                <h4 className="font-bold text-base mt-2">{card.holder_name}</h4>
                              </div>
                              <span className="text-xs tracking-tight bg-slate-950/40 px-2 py-1 rounded text-white/90">عضو شبکه شتاب</span>
                            </div>

                            <div className="my-6 text-xl tracking-widest font-mono font-semibold text-center select-all z-10 relative">
                              {card.card_number.replace(/(\d{4})/g, "$1 ").trim()}
                            </div>

                            <div className="flex justify-between items-center z-10 relative border-t border-white/10 pt-3">
                              <span className="text-[10px] text-white/70">تاسیس حساب درگاه: ۱۳۹۹</span>
                              <div className="flex items-center gap-2">
                                <button 
                                  onClick={() => handleToggleCardActive(card.id)}
                                  className={`px-3 py-1 text-xs rounded-md font-semibold transition ${card.active ? "bg-emerald-500/30 text-emerald-200" : "bg-yellow-500/30 text-yellow-200"}`}
                                >
                                  {card.active ? "فعال و روشن" : "غیرفعال"}
                                </button>
                                <button 
                                  onClick={() => handleDeleteCard(card.id)}
                                  className="p-1 bg-rose-500/20 rounded hover:bg-rose-500 text-rose-200 hover:text-white transition"
                                >
                                  <Trash2 className="h-3.5 w-3.5" />
                                </button>
                              </div>
                            </div>
                          </div>
                        );
                      })}
                    </div>
                  </div>
                </div>
              )}

              {/* تب تنظیمات درگاه */}
              {activeAdminTab === "settings" && (
                <div className="space-y-6">
                  <div>
                    <h2 className="text-lg font-bold text-white">تنظیمات درگاه کارت به کارت</h2>
                    <p className="text-xs text-slate-400 mt-1">تغییر الگوها، پوسته‌ها، ملزومات فلو و یکپارچه‌سازی وب‌سرویس‌های امنیتی</p>
                  </div>

                  <div className="bg-slate-900 border border-slate-800 rounded-xl p-6 space-y-6">
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                      
                      <div className="space-y-4">
                        <h3 className="text-sm font-bold text-blue-400 border-b border-slate-800 pb-2 flex items-center gap-2">
                          <Eye className="h-4 w-4" /> فلو و ظاهر عمومی
                        </h3>

                        <div className="flex items-center justify-between">
                          <div>
                            <label className="text-sm font-medium text-slate-200">فعال بودن آپلود فیش رسید</label>
                            <p className="text-xs text-slate-400 mt-0.5">امکان آپلود تصاویر رسید با محدودیت ۲ مگابایت</p>
                          </div>
                          <input 
                            type="checkbox" 
                            checked={settings.enable_receipt}
                            onChange={(e) => handleSettingsFormChange({ enable_receipt: e.target.checked })}
                            className="w-5 h-5 accent-blue-600 rounded"
                          />
                        </div>

                        <div className="space-y-1">
                          <label className="text-xs text-slate-400">الزام وارد کردن ۴ رقم آخر فرستنده</label>
                          <select 
                            value={settings.require_last4}
                            onChange={(e) => handleSettingsFormChange({ require_last4: e.target.value as any })}
                            className="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-lg text-sm text-slate-300"
                          >
                            <option value="required">فعال و کاملاً اجباری (توصیه شده)</option>
                            <option value="optional">فعال اما اختیاری</option>
                            <option value="none">غیرفعال و کاملاً پنهان</option>
                          </select>
                        </div>

                        <div className="space-y-1">
                          <label className="text-xs text-slate-400">پوسته بصری درگاه فرانت‌اند</label>
                          <select 
                            value={settings.theme}
                            onChange={(e) => handleSettingsFormChange({ theme: e.target.value as any })}
                            className="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-lg text-sm text-slate-300"
                          >
                            <option value="glassmorphism">تم فوق‌العاده لوکس شیشه‌ای (فناوری Glassmorphism)</option>
                            <option value="light">پوسته روشن کلاسیک</option>
                            <option value="dark">پوسته تاریک مدرن</option>
                          </select>
                        </div>
                      </div>

                      <div className="space-y-4">
                        <h3 className="text-sm font-bold text-blue-400 border-b border-slate-800 pb-2 flex items-center gap-2">
                          <Smartphone className="h-4 w-4" /> اطلاع‌رسانی پیامکی و تلگرام
                        </h3>

                        <div className="flex items-center justify-between">
                          <div>
                            <label className="text-sm font-medium text-slate-200">سرویس اطلاع‌رسانی پیامک مدیر/کاربر</label>
                            <p className="text-xs text-slate-400 mt-0.5">ارسال اس‌ام‌اس تراکنش جدید (سامانه کاوه‌نگار)</p>
                          </div>
                          <input 
                            type="checkbox" 
                            checked={settings.enable_sms}
                            onChange={(e) => handleSettingsFormChange({ enable_sms: e.target.checked })}
                            className="w-5 h-5 accent-blue-600 rounded"
                          />
                        </div>

                        {settings.enable_sms && (
                          <div className="space-y-3 bg-slate-950 p-4 rounded-lg border border-slate-800">
                            <div className="space-y-1">
                              <label className="text-[11px] text-slate-500">کلید وب‌سرویس پیامکی کاوه‌نگار (API Key)</label>
                              <input 
                                type="password" 
                                value={settings.sms_api_key}
                                onChange={(e) => handleSettingsFormChange({ sms_api_key: e.target.value })}
                                className="w-full px-2.5 py-1.5 bg-slate-900 border border-slate-800 rounded text-xs font-mono"
                              />
                            </div>
                            <div className="grid grid-cols-2 gap-2">
                              <div>
                                <label className="text-[11px] text-slate-500">شماره خط فرستنده</label>
                                <input 
                                  type="text" 
                                  value={settings.sms_sender}
                                  onChange={(e) => handleSettingsFormChange({ sms_sender: e.target.value })}
                                  className="w-full px-2.5 py-1.5 bg-slate-900 border border-slate-800 rounded text-xs font-mono"
                                />
                              </div>
                              <div>
                                <label className="text-[11px] text-slate-500">موبایل همراه مدیر</label>
                                <input 
                                  type="text" 
                                  value={settings.sms_admin_mobile}
                                  onChange={(e) => handleSettingsFormChange({ sms_admin_mobile: e.target.value })}
                                  className="w-full px-2.5 py-1.5 bg-slate-900 border border-slate-800 rounded text-xs font-mono"
                                />
                              </div>
                            </div>
                          </div>
                        )}

                        <div className="flex items-center justify-between border-t border-slate-800 pt-3">
                          <div>
                            <label className="text-sm font-medium text-slate-200">ربات تلگرام اعلان تراکنش‌ها</label>
                            <p className="text-xs text-slate-400 mt-0.5">مخابره گزارش به کانال یا گروه شخصی ادمین</p>
                          </div>
                          <input 
                            type="checkbox" 
                            checked={settings.enable_telegram}
                            onChange={(e) => handleSettingsFormChange({ enable_telegram: e.target.checked })}
                            className="w-5 h-5 accent-blue-600 rounded"
                          />
                        </div>

                        {settings.enable_telegram && (
                          <div className="space-y-3 bg-slate-950 p-4 rounded-lg border border-slate-800">
                            <div className="space-y-1">
                              <label className="text-[11px] text-slate-500">توکن بات تلگرام (Bot Token)</label>
                              <input 
                                type="password" 
                                value={settings.telegram_bot_token}
                                onChange={(e) => handleSettingsFormChange({ telegram_bot_token: e.target.value })}
                                className="w-full px-2.5 py-1.5 bg-slate-900 border border-slate-800 rounded text-xs font-mono"
                              />
                            </div>
                            <div className="space-y-1">
                              <label className="text-[11px] text-slate-500">شناسه چت تلگرام ناظر ادمین (Chat ID)</label>
                              <input 
                                type="text" 
                                value={settings.telegram_chat_id}
                                onChange={(e) => handleSettingsFormChange({ telegram_chat_id: e.target.value })}
                                className="w-full px-2.5 py-1.5 bg-slate-900 border border-slate-800 rounded text-xs font-mono animate-pulse"
                              />
                            </div>
                          </div>
                        )}
                      </div>
                    </div>

                    <div className="border-t border-slate-800 pt-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                      {/* بخش کپچا */}
                      <div className="space-y-3">
                        <h3 className="text-sm font-bold text-blue-400 flex items-center gap-1.5"><AlertCircle className="h-4 w-4" /> گوگل reCAPTCHA v2</h3>
                        <div className="space-y-1">
                          <label className="text-xs text-slate-400">کلید سایت (Site Key)</label>
                          <input 
                            type="text" 
                            value={settings.recaptcha_site_key}
                            onChange={(e) => handleSettingsFormChange({ recaptcha_site_key: e.target.value })}
                            className="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-lg text-xs font-mono"
                          />
                        </div>
                        <div className="space-y-1">
                          <label className="text-xs text-slate-400">کلید سری (Secret Key)</label>
                          <input 
                            type="password" 
                            value={settings.recaptcha_secret_key}
                            onChange={(e) => handleSettingsFormChange({ recaptcha_secret_key: e.target.value })}
                            className="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-lg text-xs font-mono"
                          />
                        </div>
                      </div>

                      {/* بخش ایمیل الگو */}
                      <div className="space-y-3">
                        <h3 className="text-sm font-bold text-blue-400 flex items-center gap-1.5"><FileText className="h-4 w-4" /> الگوی ایمیل اطلاع‌رسانی</h3>
                        <textarea 
                          rows={4}
                          value={settings.email_template}
                          onChange={(e) => handleSettingsFormChange({ email_template: e.target.value })}
                          className="w-full p-3 bg-slate-950 border border-slate-800 rounded-lg text-xs text-slate-300 focus:outline-none"
                        ></textarea>
                        <p className="text-[10px] text-slate-500">کدهای جانشین: <code>{`{full_name}`}</code> (نام) | <code>{`{mobile}`}</code> (موبایل) | <code>{`{amount}`}</code> (مبلغ) | <code>{`{order_id}`}</code> (سفارش)</p>
                      </div>
                    </div>

                    <div className="flex justify-end pt-4 border-t border-slate-800">
                      <button 
                        onClick={() => {
                          addLog("SETTINGS_UPDATED", "تنظیمات افزونه با موفقیت بازنویسی و اعمال شدند.");
                          alert("بروزرسانی لایو تنظیمات افزونه ثبت و به درگاه فرانت‌اند مخابره شد.");
                        }}
                        className="px-6 py-2.5 bg-blue-600 hover:bg-blue-500 active:bg-blue-700 text-white font-bold rounded-xl text-xs shadow cursor-pointer"
                      >
                        ذخیره نهایی تنظیمات
                      </button>
                    </div>
                  </div>
                </div>
              )}

              {/* تب لاگ‌های سیستم */}
              {activeAdminTab === "logs" && (
                <div className="space-y-5">
                  <div className="flex justify-between items-center">
                    <div>
                      <h2 className="text-lg font-bold text-white">لاگ رویدادهای سیستم (wp_c2c_logs)</h2>
                      <p className="text-xs text-slate-400 mt-1">سوابق امنیتی و ثبت تغییرات و تراکنش‌ها جهت توسعه‌دهنده</p>
                    </div>
                    <button 
                      onClick={() => {
                        setLogs([]);
                        addLog("GENERAL", "تمامی لاگ‌های سیستمی توسط توسعه دهنده پاکسازی شد.");
                      }}
                      className="px-3 py-1.5 bg-rose-600/10 text-rose-400 hover:bg-rose-600 hover:text-white rounded-lg text-xs transition b-none cursor-pointer"
                    >
                      پاکسازی کل لاگ‌ها
                    </button>
                  </div>

                  <div className="border border-slate-850 rounded-xl overflow-hidden bg-slate-950">
                    <div className="max-h-[450px] overflow-y-auto divide-y divide-slate-900 font-mono text-[13px] leading-relaxed">
                      {logs.length === 0 ? (
                        <div className="p-8 text-center text-slate-500">لاگی ثبت نشده است.</div>
                      ) : (
                        logs.map((log) => (
                          <div key={log.id} className="p-3 px-4 hover:bg-slate-900/10 flex flex-col md:flex-row gap-3 justify-between items-start md:items-center">
                            <div className="flex gap-3 items-start md:items-center">
                              <span className="text-[10px] bg-indigo-600/20 text-indigo-400 border border-indigo-500/10 px-1.5 py-0.5 rounded uppercase font-semibold shrink-0">
                                {log.event}
                              </span>
                              <span className="text-slate-300 font-sans font-medium">{log.message}</span>
                            </div>
                            <div className="flex gap-4 items-center shrink-0 text-[11px] text-slate-500">
                              <span>IP: {log.ip_address}</span>
                              <span className="text-slate-600">|</span>
                              <span>{new Date(log.created_at).toLocaleTimeString("fa-IR")}</span>
                            </div>
                          </div>
                        ))
                      )}
                    </div>
                  </div>
                </div>
              )}

              {/* تب خروجی کارت ها اکسل */}
              {activeAdminTab === "export" && (
                <div className="space-y-6">
                  <div>
                    <h2 className="text-lg font-bold text-white">خروجی اکسل و گزارش‌گیری چند ستونه</h2>
                    <p className="text-xs text-slate-400 mt-1">تولید فیش چند ستونه خروجیهای ممیزی با تضمین فونت فارسی استاندارد</p>
                  </div>

                  <div className="bg-slate-900 border border-slate-800 rounded-xl p-6 text-center max-w-lg mx-auto space-y-5">
                    <Layout className="h-12 w-12 text-blue-500 mx-auto" />
                    
                    <h3 className="text-base font-bold text-slate-100">دانلود گزارش فیش‌های دریافتی (قالب CSV فارسی)</h3>
                    <p className="text-xs text-slate-400 line-height-relaxed">
                      ما از هدر یو‌تی‌اف لوکس BOM جهت حل به هم ریختگی فونت فارسی در برنامه‌هایی نظیر اکسل استفاده کرده‌ایم. با کلیک بر روی دکمه زیر جدول تراکنش‌های شبیه‌ساز را فوراً با پسوند .csv استخراج فرمایید.
                    </p>

                    <div className="bg-slate-950 p-4 rounded-lg border border-slate-800 text-right space-y-1.5 text-xs text-slate-400">
                      <div>• تعداد تراکنش‌های آماده خروج: {transactions.length} عدد</div>
                      <div>• انکدینگ تضمینی: UTF-8 BOM</div>
                      <div>• قابلیت ادغام: سازگاری با سیستم‌های مالی حسابداری ایرانی</div>
                    </div>

                    <button 
                      onClick={() => {
                        // تولید و دانلود فایل فرضی با BOM در مرورگر
                        const bom = "\uFEFF";
                        const headers = ["شناسه", "سفارش", "خریدار", "موبایل", "مبلغ (تومان)", "کارت", "وضعیت", "تاریخ"];
                        const rows = transactions.map(t => [
                          t.id,
                          t.order_id || "پرداخت مستقیم",
                          t.full_name,
                          t.mobile,
                          t.amount,
                          t.bank_card_id,
                          t.status === "approved" ? "تایید شده" : (t.status === "rejected" ? "رد شده" : "معلق"),
                          t.created_at
                        ]);
                        const csvContent = bom + [headers, ...rows].map(e => e.join(",")).join("\n");
                        const blob = new Blob([csvContent], { type: "text/csv;charset=utf-8;" });
                        const link = document.createElement("a");
                        link.href = URL.createObjectURL(blob);
                        link.setAttribute("download", `cards-report-${new Date().toISOString().substring(0,10)}.csv`);
                        document.body.appendChild(link);
                        link.click();
                        document.body.removeChild(link);
                        addLog("CSV_EXPORTED", "گزارش اکسل CSV تراکنش‌های شتابی تولید و دریافت شد.");
                      }}
                      className="px-6 py-2.5 bg-blue-600 hover:bg-blue-500 text-white font-bold text-xs rounded-xl cursor-pointer shadow-md"
                    >
                      خروجی گرفتن نهایی (CSV Excel)
                    </button>
                  </div>
                </div>
              )}

            </div>
          </div>
        </section>

        {/* ======================================================== */}
        {/* پنل راست: پیش‌نمایش سایت فروشگاه (Customer Frontend Preview) */}
        {/* ======================================================== */}
        <section className="col-span-1 xl:col-span-5 p-6 flex flex-col bg-slate-900 gap-6 overflow-y-auto">
          
          <div className="flex flex-col md:flex-row justify-between items-start md:items-center gap-3">
            <div>
              <h2 className="text-base font-bold text-white flex items-center gap-2">
                <Eye className="h-5 w-5 text-blue-500 animate-pulse" />
                پیش‌نمایش زنده سمت مشتری
              </h2>
              <p className="text-xs text-slate-400 mt-0.5">مشاهده کنید درگاه به صورت کاملا پویا روی سایت چطور رندر می‌شود</p>
            </div>

            <div className="inline-flex bg-slate-950 p-1 rounded-lg border border-slate-800">
              <button 
                onClick={() => setCustomerFormTab("checkout")}
                className={`px-3 py-1.5 text-xs rounded-md font-semibold transition cursor-pointer ${customerFormTab === "checkout" ? "bg-blue-600 text-white" : "text-slate-400 hover:text-slate-200"}`}
              >
                صفحه تسویه ووکامرس
              </button>
              <button 
                onClick={() => setCustomerFormTab("shortcode")}
                className={`px-3 py-1.5 text-xs rounded-md font-semibold transition cursor-pointer ${customerFormTab === "shortcode" ? "bg-blue-600 text-white" : "text-slate-400 hover:text-slate-200"}`}
              >
                شورتکد مستقل فرم
              </button>
            </div>
          </div>

          {/* فریم لوکس شبیه‌ساز آیپد/مرورگر موبایل */}
          <div className="flex-1 bg-slate-950 rounded-2xl border-4 border-slate-850 shadow-2xl relative flex flex-col overflow-hidden max-w-lg mx-auto w-full">
            
            {/* ناوبار مرورگر فرضی */}
            <div className="bg-slate-900 px-4 py-2 flex items-center gap-2 border-b border-slate-850 shrink-0">
              <div className="flex gap-1.5 shrink-0">
                <span className="w-2.5 h-2.5 bg-red-500 rounded-full"></span>
                <span className="w-2.5 h-2.5 bg-yellow-500 rounded-full"></span>
                <span className="w-2.5 h-2.5 bg-green-500 rounded-full"></span>
              </div>
              <div className="flex-1 bg-slate-950 rounded-md text-[10px] text-slate-400 text-center py-1 truncate font-mono select-none px-2 text-right">
                https://my-shop.ir/checkout
              </div>
            </div>

            {/* بدنه اسکرولی آیفون/آیپد شبیه‌ساز */}
            <div className="flex-grow p-4 overflow-y-auto bg-slate-950 relative min-h-[420px]">
              
              {checkoutStep === "form" ? (
                <div style={{ direction: "rtl", textAlign: "right" }} className="space-y-4">
                  
                  {customerFormTab === "checkout" ? (
                    /* ۱. نمای صفحه ووکامرس */
                    <div className="space-y-3">
                      <div className="p-3.5 bg-slate-900 rounded-xl border border-slate-850">
                        <h4 className="text-xs text-slate-400 font-bold uppercase tracking-wide">۱. خلاصه سبد خرید</h4>
                        <div className="flex justify-between items-center text-sm font-semibold text-white mt-2">
                          <span>قالب مدرن شبیه‌سازی وب فارسی</span>
                          <span className="text-emerald-400">۶۴۰,۰۰۰ تومان</span>
                        </div>
                      </div>

                      <div className="bg-slate-900 p-3.5 rounded-xl border border-slate-850 space-y-3">
                        <h4 className="text-xs text-slate-400 font-bold">۲. شیوه پرداخت نهایی</h4>
                        
                        <div className="p-2.5 bg-blue-600/10 text-blue-400 border border-blue-500/20 rounded-lg flex items-center justify-between text-xs">
                          <span className="font-semibold">{settings.theme === "glassmorphism" ? "درگاه شتاب کارت به کارت (شیشه‌ای)" : "درگاه کارت به کارت دستی"}</span>
                          <span className="bg-blue-500 w-3 h-3 rounded-full flex items-center justify-center p-0.5"><Check className="h-2 w-2 text-white" /></span>
                        </div>
                      </div>
                    </div>
                  ) : (
                    /* ۲. پیش‌گفتار شورتکد */
                    <div className="text-center py-2">
                      <span className="text-[10px] bg-slate-800 text-slate-400 px-3 py-1 rounded-full font-mono font-semibold">[card_to_card_form]</span>
                    </div>
                  )}

                  {/* ======================================================== */}
                  {/* فرم کارت به کارت منطبق بر گزینه‌های لایو (Light, Dark, Glass) */}
                  {/* ======================================================== */}
                  <form onSubmit={handleCheckoutSubmit} className="space-y-4">
                    
                    <div className={`p-5 rounded-2xl transition-all duration-300 ${
                      settings.theme === "glassmorphism" 
                        ? "bg-white/10 backdrop-blur-md border border-white/20 shadow-xl"
                        : settings.theme === "light"
                          ? "bg-slate-50 text-slate-900 border border-slate-200 shadow"
                          : "bg-slate-900 text-slate-100 border border-slate-800 shadow"
                    }`}>
                      
                      <h3 className={`text-sm font-bold text-center mb-1 ${settings.theme === "light" ? "text-slate-900" : "text-white"}`}>
                        واریز به حساب فروشگاه (کارت به کارت)
                      </h3>
                      <p className={`text-[10px] text-center mb-4 leading-relaxed ${settings.theme === "light" ? "text-slate-500" : "text-slate-400"}`}>
                        لطفا مبلغ مورد نظر را به کارت شتابی مقصد فروشگاه انتقال داده و جزئیات رسید را ثبت فرمایید.
                      </p>

                      {/* لیست کارت‌ها همراه با دکمه کارت */}
                      <div className="space-y-2">
                        <label className={`text-xs font-bold block ${settings.theme === "light" ? "text-slate-800" : "text-slate-300"}`}>شماره کارت‌های مقصد ما:</label>
                        
                        <div className="grid grid-cols-1 gap-2.5">
                          {cards.filter(c => c.active).map((card) => {
                            const isSelected = custSelectedCard === card.id;
                            return (
                              <div 
                                key={card.id}
                                onClick={() => setCustSelectedCard(card.id)}
                                className={`p-3 rounded-xl cursor-pointer border transition duration-200 flex justify-between items-center ${
                                  isSelected 
                                    ? "bg-blue-600/10 border-blue-500" 
                                    : settings.theme === "light"
                                      ? "bg-white border-slate-200 hover:border-slate-300"
                                      : "bg-slate-950/60 border-slate-800 hover:border-slate-700"
                                }`}
                              >
                                <div>
                                  <div className="flex items-center gap-1.5">
                                    <span className={`w-2 h-2 rounded-full ${isSelected ? "bg-blue-500" : "bg-slate-500"}`}></span>
                                    <span className={`text-xs font-bold ${settings.theme === "light" ? "text-slate-900" : "text-white"}`}>{card.bank_name}</span>
                                  </div>
                                  <div className={`font-mono text-sm tracking-wide mt-1 ${settings.theme === "light" ? "text-slate-700" : "text-slate-300"}`}>
                                    {card.card_number.replace(/(\d{4})/g, "$1 ").trim()}
                                  </div>
                                  <div className={`text-[10px] mt-0.5 ${settings.theme === "light" ? "text-slate-500" : "text-slate-400"}`}>صاحب حساب: {card.holder_name}</div>
                                </div>

                                <div className="p-1 bg-white rounded border border-slate-100">
                                  <img 
                                    src={`https://chart.googleapis.com/chart?chs=80&cht=qr&chl=${encodeURIComponent(card.card_number)}`}
                                    alt="QR" 
                                    className="w-10 h-10 display-block"
                                  />
                                </div>
                              </div>
                            );
                          })}
                        </div>
                      </div>

                      <hr className={`my-4 border-dashed ${settings.theme === "light" ? "border-slate-300" : "border-slate-800"}`} />

                      {/* فیلدهای ورودی مشتری */}
                      <div className="space-y-3 text-right">
                        
                        <div className="space-y-1">
                          <label className={`text-xs font-semibold ${settings.theme === "light" ? "text-slate-800" : "text-slate-300"}`}>نام و نام خانوادگی واریز کننده <span className="text-red-500">*</span></label>
                          <input 
                            type="text"
                            required
                            placeholder="مانند: محمد حسینی"
                            value={custName}
                            onChange={(e) => setCustName(e.target.value)}
                            className={`w-full px-3 py-2 text-xs rounded-lg border focus:outline-none ${
                              settings.theme === "light"
                                ? "bg-white border-slate-300 text-slate-900 focus:border-blue-500"
                                : "bg-slate-950/80 border-slate-800 text-slate-100 focus:border-blue-500"
                            }`}
                          />
                        </div>

                        <div className="space-y-1">
                          <label className={`text-xs font-semibold ${settings.theme === "light" ? "text-slate-800" : "text-slate-300"}`}>شماره موبایل جهت هماهنگی و پیامک <span className="text-red-500">*</span></label>
                          <input 
                            type="tel"
                            required
                            placeholder="09121234567"
                            value={custMobile}
                            onChange={(e) => setCustMobile(e.target.value)}
                            className={`w-full px-3 py-2 text-xs rounded-lg border text-left focus:outline-none ${
                              settings.theme === "light"
                                ? "bg-white border-slate-300 text-slate-900 focus:border-blue-500"
                                : "bg-slate-950/80 border-slate-800 text-slate-100 focus:border-blue-500"
                            }`}
                          />
                        </div>

                        {settings.require_last4 !== "none" && (
                          <div className="space-y-1">
                            <label className={`text-xs font-semibold ${settings.theme === "light" ? "text-slate-800" : "text-slate-300"}`}>
                              ۴ رقم آخر کارت فرستنده شما 
                              {settings.require_last4 === "required" && <span className="text-red-500"> *</span>}
                            </label>
                            <input 
                              type="text"
                              maxLength={4}
                              required={settings.require_last4 === "required"}
                              placeholder="1234"
                              value={custLast4}
                              onChange={(e) => setCustLast4(e.target.value.replace(/\D/g, ""))}
                              className={`w-full px-3 py-2 text-xs rounded-lg border text-left focus:outline-none ${
                                settings.theme === "light"
                                  ? "bg-white border-slate-300 text-slate-900 focus:border-blue-500"
                                  : "bg-slate-950/80 border-slate-800 text-slate-100 focus:border-blue-500"
                              }`}
                            />
                          </div>
                        )}

                        {settings.enable_receipt && (
                          <div className="space-y-1">
                            <label className={`text-xs font-semibold ${settings.theme === "light" ? "text-slate-800" : "text-slate-300"}`}>تصویر رسید چاپی یا اسکرین‌شات واریز</label>
                            
                            <div className={`p-4 rounded-xl border-2 border-dashed text-center cursor-pointer transition ${
                              settings.theme === "light"
                                ? "border-slate-300 bg-white hover:bg-slate-100"
                                : "border-slate-800 bg-slate-950 hover:bg-slate-900"
                            }`}>
                              <input 
                                type="file"
                                id="custReceiptFile"
                                accept="image/*"
                                onChange={handleFileDrop}
                                className="hidden"
                              />
                              <label htmlFor="custReceiptFile" className="cursor-pointer space-y-1 block">
                                <Upload className="h-5 w-5 text-blue-500 mx-auto" />
                                <span className="text-[11px] text-slate-400 block">
                                  {custReceiptName ? `فایل انتخاب شده: ${custReceiptName}` : "رسید واریز را انتخاب کنیدیا به اینجا بکشید"}
                                </span>
                              </label>
                            </div>
                            {custReceipt && (
                              <div className="mt-2 text-center">
                                <img src={custReceipt} alt="Receipt preview" className="max-h-24 mx-auto rounded border border-slate-800 shadow" />
                              </div>
                            )}
                          </div>
                        )}

                        {/* شبیه‌ساز ری‌کپچا */}
                        {settings.recaptcha_site_key !== "" && (
                          <div className={`p-2.5 rounded-lg border mt-2 flex items-center justify-between text-xs ${
                            settings.theme === "light" ? "bg-white border-slate-200 text-slate-700" : "bg-slate-950 border-slate-850 text-slate-400"
                          }`}>
                            <div className="flex items-center gap-1.5">
                              <input 
                                type="checkbox" 
                                checked={custRecaptcha}
                                onChange={(e) => setCustRecaptcha(e.target.checked)}
                                className="w-4 h-4 rounded text-blue-600"
                              />
                              <span>من ربات نیستم (reCAPTCHA v2)</span>
                            </div>
                            <img src="https://www.google.com/recaptcha/about/images/reCAPTCHA-logo@2x.png" alt="Captcha" className="h-5" />
                          </div>
                        )}

                      </div>

                      {formError && (
                        <div className="p-2.5 bg-rose-500/10 text-rose-400 border border-rose-500/15 rounded-lg text-xs mt-3 flex items-center gap-1.5 font-medium">
                          <AlertCircle className="h-4 w-4" />
                          {formError}
                        </div>
                      )}

                      <button 
                        type="submit"
                        className="w-full mt-5 py-2.5 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 text-white font-bold text-xs rounded-xl shadow cursor-pointer text-center transition"
                      >
                        {customerFormTab === "checkout" ? "ثبت نهایی و ثبت سفارش و پرداخت" : "ثبت مشخصات و واریز وجه"}
                      </button>

                    </div>
                  </form>
                </div>
              ) : (
                /* صفحه تایید و تشکر پرداخت موفق */
                <div style={{ direction: "rtl", textAlign: "right" }} className="p-3">
                  <div className="bg-emerald-500/10 border border-emerald-500/20 text-center rounded-2xl p-6 space-y-4 shadow-lg">
                    <div className="bg-emerald-500 w-12 h-12 rounded-full inline-flex items-center justify-center text-white text-xl mx-auto shadow shadow-emerald-500/40">✓</div>
                    
                    <div>
                      <h3 className="text-emerald-400 font-bold text-base">مشخصات پرداخت با موفقیت ثبت شد</h3>
                      <p className="text-[11px] text-slate-400 mt-1 line-height-relaxed">اطلاعات فیش بانکی شما دریافت شد و برای ناظر مدیریت وب‌سایت مخابره گردید. در اسرع وقت تایید شده و پردازش سفارش شما آغاز خواهد شد.</p>
                    </div>

                    <div className="bg-slate-950 p-3 rounded-lg border border-slate-900 text-right space-y-2 text-xs text-slate-400 border-dashed">
                      <div className="flex justify-between border-b border-slate-900 pb-1.5">
                        <span>شناسه پیگیری فیش:</span>
                        <strong className="text-white">#{lastSubmittedTx?.id}</strong>
                      </div>
                      <div className="flex justify-between border-b border-slate-900 pb-1.5">
                        <span>نام واریز کننده:</span>
                        <strong className="text-white">{lastSubmittedTx?.full_name}</strong>
                      </div>
                      <div className="flex justify-between border-b border-slate-900 pb-1.5">
                        <span>شماره همراه پیگیری:</span>
                        <strong className="text-white">{lastSubmittedTx?.mobile}</strong>
                      </div>
                      <div className="flex justify-between text-emerald-400">
                        <span>مبلغ ارسال شده:</span>
                        <strong>{lastSubmittedTx?.amount.toLocaleString()} تومان</strong>
                      </div>
                    </div>

                    <button 
                      onClick={handleResetCheckoutForm}
                      className="w-full py-2 bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold rounded-lg transition mt-4 cursor-pointer"
                    >
                      ثبت یک پرداخت یا پیگیری ممتد
                    </button>
                  </div>
                </div>
              )}

            </div>
          </div>
        </section>

      </main>

      {/* مودال یادداشت ادمین */}
      <AnimatePresence>
        {selectedTxForNote && (
          <div className="fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center p-4 z-50">
            <motion.div 
              initial={{ scale: 0.95, opacity: 0 }}
              animate={{ scale: 1, opacity: 1 }}
              exit={{ scale: 0.95, opacity: 0 }}
              className="bg-slate-900 border border-slate-800 p-6 rounded-2xl w-full max-w-md space-y-4 shadow-xl"
            >
              <div className="flex justify-between items-center text-right" style={{ direction: "rtl" }}>
                <h3 className="font-bold text-white text-base">ثبت یادداشت و علل رد/تایید تراکنش #{selectedTxForNote.id}</h3>
                <button onClick={() => setSelectedTxForNote(null)} className="text-slate-400 hover:text-white cursor-pointer"><X className="h-5 w-5" /></button>
              </div>

              <div className="space-y-3" style={{ direction: "rtl", textAlign: "right" }}>
                <p className="text-xs text-slate-400">یادداشت برای خریدار "{selectedTxForNote.full_name}" (صرفا جهت ممیزی‌های اداری ادمین):</p>
                
                <textarea 
                  rows={4}
                  value={modalAdminNotes}
                  onChange={(e) => setModalAdminNotes(e.target.value)}
                  placeholder="موردی جهت عیب‌یابی، ثبت تراکنش یا ممیزی بنویسید..."
                  className="w-full p-3 bg-slate-950 border border-slate-800 rounded-lg text-xs text-slate-300 focus:outline-none focus:border-blue-500"
                ></textarea>
              </div>

              <div className="flex gap-2.5 justify-end" style={{ direction: "rtl" }}>
                <button 
                  onClick={handleSaveModalNotes}
                  className="px-4 py-2 bg-blue-600 hover:bg-blue-500 text-white font-bold text-xs rounded-xl cursor-pointer"
                >
                  ثبت نهایی یادداشت
                </button>
                <button 
                  onClick={() => setSelectedTxForNote(null)}
                  className="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs rounded-xl cursor-pointer"
                >
                  انصراف
                </button>
              </div>
            </motion.div>
          </div>
        )}
      </AnimatePresence>

    </div>
  );
}
