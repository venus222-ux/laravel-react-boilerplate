import { Link, NavLink, useNavigate } from "react-router-dom";
import { useStore } from "../store/useStore";
import styles from "./Navbar.module.css";

export default function Navbar() {
  const { isAuth, setIsAuth, theme, toggleTheme } = useStore();
  const navigate = useNavigate();

  const handleLogout = () => {
    setIsAuth(false);
    localStorage.removeItem("token");
    navigate("/login");
  };

  return (
    <div
      className={`${styles.navWrapper} ${theme === "dark" ? styles.dark : ""}`}
    >
      <nav className={styles.glassNav}>
        {/* Brand with Gradient Text */}
        <Link className={styles.brand} to="/">
          <span style={{ fontSize: "1.2rem" }}>⚡</span>
          <span>MESSENGER</span>
        </Link>

        {/* Navigation Segments */}
        <div className={styles.navGroup}>
          <NavLink
            to="/dashboard"
            className={({ isActive }) =>
              `${styles.link} ${isActive ? styles.activeLink : ""}`
            }
          >
            Dashboard
          </NavLink>
          <NavLink
            to="/profile"
            className={({ isActive }) =>
              `${styles.link} ${isActive ? styles.activeLink : ""}`
            }
          >
            Profile
          </NavLink>
        </div>

        {/* Actions Group */}
        <div className={styles.controls}>
          <button
            className={styles.iconBtn}
            onClick={toggleTheme}
            aria-label="Toggle Theme"
          >
            {theme === "light" ? "🌙" : "☀️"}
          </button>

          {isAuth && (
            <button className={styles.logout} onClick={handleLogout}>
              Logout
            </button>
          )}
        </div>
      </nav>
    </div>
  );
}
