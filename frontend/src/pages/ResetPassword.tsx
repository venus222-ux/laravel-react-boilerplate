import { useState, FormEvent } from "react";
import { useNavigate, useParams, useSearchParams } from "react-router-dom";
import API from "../api";
import { toast } from "react-toastify";

import styles from "./ResetPassword.module.css";

export default function ResetPassword() {
  const { token } = useParams();
  const [searchParams] = useSearchParams();
  const email = searchParams.get("email") || "";

  const [password, setPassword] = useState("");
  const [passwordConfirmation, setPasswordConfirmation] = useState("");

  const navigate = useNavigate();

  const handleSubmit = async (e: FormEvent) => {
    e.preventDefault();

    if (password !== passwordConfirmation) {
      toast.error("Passwords do not match");
      return;
    }

    if (password.length < 6) {
      toast.error("Password must be at least 6 characters");
      return;
    }

    try {
      await API.post("/reset-password", {
        email,
        token,
        password,
        password_confirmation: passwordConfirmation,
      });

      // Clear any stale auth data
      localStorage.removeItem("token");
      localStorage.removeItem("user");

      toast.success("Password reset successful! Please log in.");
      navigate("/login");
    } catch (err: any) {
      const msg =
        err?.response?.data?.message ||
        err?.response?.data?.error ||
        "Password reset failed. Please try again.";
      toast.error(msg);
    }
  };

  return (
    <div className={styles.wrapper}>
      <div className={styles.card}>
        <h2 className={styles.title}>
          <span>🔒</span> Reset Password
        </h2>

        <p className={styles.subtitle}>
          Enter your new password for <strong>{email || "your account"}</strong>
        </p>

        <form onSubmit={handleSubmit}>
          {/* Hidden email field - good for form consistency & accessibility */}
          <input type="hidden" name="email" value={email} />

          <div className={styles.formGroup}>
            <input
              className={styles.input}
              type="password"
              placeholder="New password"
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              required
              minLength={6}
              autoComplete="new-password"
              autoFocus
            />
          </div>

          <div className={styles.formGroup}>
            <input
              className={styles.input}
              type="password"
              placeholder="Confirm new password"
              value={passwordConfirmation}
              onChange={(e) => setPasswordConfirmation(e.target.value)}
              required
              autoComplete="new-password"
            />
          </div>

          <button type="submit" className={styles.btn}>
            Reset Password
          </button>
        </form>
      </div>
    </div>
  );
}
