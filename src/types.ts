export interface BankCard {
  id: number;
  card_number: string;
  bank_name: string;
  holder_name: string;
  active: boolean;
  created_at: string;
}

export interface Transaction {
  id: number;
  order_id: number | null;
  user_id: number | null;
  full_name: string;
  mobile: string;
  amount: number;
  bank_card_id: number;
  last4digits: string;
  receipt_url: string | null;
  status: "pending" | "approved" | "rejected";
  admin_notes: string;
  ip_address: string;
  created_at: string;
}

export interface AuditLog {
  id: number;
  event: string;
  message: string;
  ip_address: string;
  user_id: number;
  created_at: string;
}

export interface GatewaySettings {
  enable_receipt: boolean;
  require_last4: "required" | "optional" | "none";
  enable_sms: boolean;
  sms_api_key: string;
  sms_sender: string;
  sms_admin_mobile: string;
  enable_telegram: boolean;
  telegram_bot_token: string;
  telegram_chat_id: string;
  recaptcha_site_key: string;
  recaptcha_secret_key: string;
  theme: "light" | "dark" | "glassmorphism";
  email_template: string;
  delete_tables_on_uninstall: boolean;
}
