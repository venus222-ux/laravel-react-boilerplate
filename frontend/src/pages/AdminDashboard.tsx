import { useEffect, useState } from "react";
import API from "../api";
import { DashboardData } from "../types/ActivityLog";
import styles from "../styles/AdminDashboard.module.css";
import Sidebar from "../components/AdminDashboard/Sidebar";
import ActivityTable from "../components/AdminDashboard/ActivityTable";

type TabType = "home" | "logs" | "users";

export default function AdminDashboard() {
  const [data, setData] = useState<DashboardData | null>(null);
  const [users, setUsers] = useState<any[]>([]);
  const [error, setError] = useState<string | null>(null);
  const [currentTab, setCurrentTab] = useState<TabType>("home");

  // Fetch Dashboard Stats & Logs
  useEffect(() => {
    API.get("/admin/dashboard")
      .then((res) => setData(res.data))
      .catch((err) =>
        setError(err.response?.data?.message || "Request failed"),
      );
  }, []);

  // Fetch users when the "users" tab is clicked
  useEffect(() => {
    if (currentTab === "users") {
      fetchUsers();
    }
  }, [currentTab]);

  const fetchUsers = async () => {
    try {
      const res = await API.get("/admin/users");
      setUsers(res.data);
    } catch (err) {
      console.error("Failed to fetch users");
    }
  };

  const handleDeleteUser = async (id: number) => {
    if (!window.confirm("Are you sure you want to delete this user?")) return;

    try {
      await API.delete(`/admin/users/${id}`);
      // Update local state to remove the user
      setUsers(users.filter((user) => user.id !== id));
    } catch (err: any) {
      alert(err.response?.data?.message || "Delete failed");
    }
  };

  if (error) return <div className={styles.errorState}>⚠️ Error: {error}</div>;
  if (!data)
    return <div className={styles.loadingState}>Loading dashboard...</div>;

  return (
    <div className={styles.dashboardContainer}>
      <Sidebar currentTab={currentTab} setCurrentTab={setCurrentTab} />

      <main className={styles.mainContent}>
        {/* HOME TAB - Pure Welcome */}
        {currentTab === "home" && (
          <div className={styles.homeCentered}>
            <header className={styles.header}>
              <h1 className={styles.welcomeTitle}>Welcome back, Admin 👋</h1>
              <p className={styles.subtitle}>
                System is running smoothly. Select a tab from the sidebar to
                manage your data.
              </p>
            </header>
          </div>
        )}

        {/* LOGS TAB - Stats & Table */}
        {currentTab === "logs" && (
          <div className={styles.tabFadeIn}>
            <header className={styles.header}>
              <h2>Activity Dashboard</h2>
              <p className={styles.subtitle}>
                Complete audit trail and security metrics.
              </p>
            </header>

            <div className={styles.statsGrid}>
              <div className={styles.statCard}>
                <span className={styles.statLabel}>Logins Today</span>
                <span className={styles.statValue}>
                  {data.stats.logins_today}
                </span>
              </div>
              <div className={styles.statCard}>
                <span className={styles.statLabel}>Failed Attempts</span>
                <span className={`${styles.statValue} ${styles.dangerText}`}>
                  {data.stats.failed_logins_today}
                </span>
              </div>
              <div className={styles.statCard}>
                <span className={styles.statLabel}>Active Users</span>
                <span className={`${styles.statValue} ${styles.primaryText}`}>
                  {data.stats.active_users}
                </span>
              </div>
            </div>

            <ActivityTable activities={data.recent_activity} />
          </div>
        )}

        {/* USERS TAB - Read & Delete */}
        {currentTab === "users" && (
          <div className={styles.tabFadeIn}>
            <header className={styles.header}>
              <h2>User Management</h2>
              <p className={styles.subtitle}>
                View and manage registered system users.
              </p>
            </header>

            <div className={styles.tableWrapper}>
              <div className={styles.tableHeader}>
                Total Users: {users.length}
              </div>
              <table className={styles.adminTable}>
                <thead>
                  <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Joined</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  {users.map((user) => (
                    <tr key={user.id}>
                      <td className={styles.emailCell}>{user.name || "N/A"}</td>
                      <td>{user.email}</td>
                      <td>
                        <span className={styles.roleBadge}>
                          {user.roles?.[0]?.name || "user"}
                        </span>
                      </td>
                      <td>{new Date(user.created_at).toLocaleDateString()}</td>
                      <td>
                        <button
                          className={styles.deleteBtn}
                          onClick={() => handleDeleteUser(user.id)}
                        >
                          Delete
                        </button>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </div>
        )}
      </main>
    </div>
  );
}
