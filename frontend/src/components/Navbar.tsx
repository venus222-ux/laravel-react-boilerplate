import { Link, NavLink, useNavigate } from "react-router-dom";
import { useStore } from "../store/useStore";
import styles from "./Navbar.module.css";

export default function Navbar() {
  const { isAuth, setIsAuth, theme, toggleTheme } = useStore();
  const navigate = useNavigate();

  const handleLogout = () => {
    setIsAuth(false);
    localStorage.removeItem("token");
    // Optional: also clear role/user if you store them
    // localStorage.removeItem("role");
    navigate("/login");
  };

  return (
    <div
      className={`${styles.navWrapper} ${theme === "dark" ? styles.dark : ""}`}
    >
      <nav className={styles.glassNav}>
        {/* Brand */}
        <Link className={styles.brand} to="/">
          <span className={styles.brandIcon}>⚡</span>
          <span className={styles.brandText}>MESSENGER</span>
        </Link>

        {/* Main navigation - changes based on auth state */}
        <div className={styles.navGroup}>
          {isAuth ? (
            <>
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
            </>
          ) : (
            <>
              <NavLink
                to="/login"
                className={({ isActive }) =>
                  `${styles.link} ${isActive ? styles.activeLink : ""}`
                }
              >
                Login
              </NavLink>

              <NavLink
                to="/register"
                className={({ isActive }) =>
                  `${styles.link} ${isActive ? styles.activeLink : ""}`
                }
              >
                Register
              </NavLink>
            </>
          )}
        </div>

        {/* Right side controls */}
        <div className={styles.controls}>
          <button
            className={styles.iconBtn}
            onClick={toggleTheme}
            aria-label={`Switch to ${theme === "light" ? "dark" : "light"} mode`}
            title="Toggle theme"
          >
            {theme === "light" ? "🌙" : "☀️"}
          </button>

          {isAuth && (
            <button className={styles.logoutBtn} onClick={handleLogout}>
              Logout
            </button>
          )}
        </div>
      </nav>
    </div>
  );
}
