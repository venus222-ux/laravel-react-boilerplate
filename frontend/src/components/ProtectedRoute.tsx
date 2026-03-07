// components/ProtectedRoute.tsx

import { ReactNode } from "react";
import { Navigate } from "react-router-dom";
import { useStore } from "../store/useStore";

interface Props {
  children: ReactNode;
  role?: string; // optional role to restrict
}

const ProtectedRoute = ({ children, role }: Props) => {
  const { isAuth, role: userRole } = useStore();

  if (!isAuth) return <Navigate to="/login" />;

  if (role && userRole !== role) return <Navigate to="/dashboard" />; // redirect non-admins

  return <>{children}</>;
};

export default ProtectedRoute;
