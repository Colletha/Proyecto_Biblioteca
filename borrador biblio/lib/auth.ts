// Sistema de autenticación simulado para el sistema de biblioteca
import { getUserByEmail, getRoleById } from "./database"

export interface AuthUser {
  id: number
  nombre: string
  apellido: string
  correo: string
  rol: string
  carne?: string
}

export interface AuthSession {
  user: AuthUser | null
  isAuthenticated: boolean
}

// Simulación de sesión (en producción usaríamos JWT o sesiones del servidor)
let currentSession: AuthSession = {
  user: null,
  isAuthenticated: false,
}

export const login = async (
  email: string,
  password: string,
): Promise<{ success: boolean; user?: AuthUser; error?: string }> => {
  try {
    const user = getUserByEmail(email)

    if (!user) {
      return { success: false, error: "Usuario no encontrado" }
    }

    if (!user.activo) {
      return { success: false, error: "Usuario inactivo" }
    }

    // En producción, aquí verificaríamos el hash de la contraseña
    if (user.password !== password) {
      return { success: false, error: "Contraseña incorrecta" }
    }

    const role = getRoleById(user.rol_id)
    if (!role) {
      return { success: false, error: "Rol no válido" }
    }

    const authUser: AuthUser = {
      id: user.id,
      nombre: user.nombre,
      apellido: user.apellido,
      correo: user.correo,
      rol: role.nombre,
      carne: user.carne,
    }

    currentSession = {
      user: authUser,
      isAuthenticated: true,
    }

    return { success: true, user: authUser }
  } catch (error) {
    return { success: false, error: "Error interno del servidor" }
  }
}

export const logout = (): void => {
  currentSession = {
    user: null,
    isAuthenticated: false,
  }
}

export const getCurrentSession = (): AuthSession => {
  return currentSession
}

export const requireAuth = (): AuthUser => {
  if (!currentSession.isAuthenticated || !currentSession.user) {
    throw new Error("No autenticado")
  }
  return currentSession.user
}

export const hasRole = (requiredRole: string): boolean => {
  if (!currentSession.user) return false
  return currentSession.user.rol === requiredRole
}

export const hasAnyRole = (roles: string[]): boolean => {
  if (!currentSession.user) return false
  return roles.includes(currentSession.user.rol)
}
