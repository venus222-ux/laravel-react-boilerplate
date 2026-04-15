import styles from "../../styles/AdminDashboard.module.css";

export default function ActivityTable({ activities }: { activities: any[] }) {
  if (activities.length === 0) {
    return <div className={styles.noData}>No recent activity found.</div>;
  }

  return (
    <div className={styles.tableWrapper}>
      <div className={styles.tableHeader}>System Activity Logs</div>
      <table className={styles.adminTable}>
        <thead>
          <tr>
            <th>User Email</th>
            <th>Action</th>
            <th>Status</th>
            <th>Network Details</th>
            <th>Timestamp</th>
          </tr>
        </thead>
        <tbody>
          {activities.map((log) => (
            <tr key={log._id}>
              <td className={styles.emailCell}>{log.email}</td>
              <td>
                <span className={styles.actionText}>{log.action}</span>
              </td>
              <td>
                <span
                  className={`${styles.statusBadge} ${log.status === "success" ? styles.statusSuccess : styles.statusFailed}`}
                >
                  {log.status}
                </span>
              </td>
              <td>
                <div className={styles.monoText}>{log.ip_address}</div>
                <div className={styles.deviceText}>{log.device}</div>
              </td>
              <td className={styles.timeText}>
                {new Date(log.created_at).toLocaleDateString()} <br />
                <small>{new Date(log.created_at).toLocaleTimeString()}</small>
              </td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}
