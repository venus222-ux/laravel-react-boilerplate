import styles from "../../styles/AdminDashboard.module.css";

interface SidebarProps {
  currentTab: string;
  setCurrentTab: (tab: any) => void;
}

export default function Sidebar({ currentTab, setCurrentTab }: SidebarProps) {
  return (
    <aside className={styles.sidebar}>
      <div className={styles.logo}>ShieldAdmin</div>
      <nav className={styles.navGroup}>
        <div
          className={`${styles.navItem} ${currentTab === "home" ? styles.activeNavItem : ""}`}
          onClick={() => setCurrentTab("home")}
        >
          📊 Dashboard
        </div>
        <div
          className={`${styles.navItem} ${currentTab === "logs" ? styles.activeNavItem : ""}`}
          onClick={() => setCurrentTab("logs")}
        >
          📜 Activity Logs
        </div>
        <div
          className={`${styles.navItem} ${currentTab === "users" ? styles.activeNavItem : ""}`}
          onClick={() => setCurrentTab("users")}
        >
          👥 Users
        </div>
      </nav>
    </aside>
  );
}
