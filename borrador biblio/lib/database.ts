// Simulación de base de datos para desarrollo sin integración externa
// En producción, esto se reemplazaría con conexiones reales a MySQL/PostgreSQL

export interface Role {
  id: number
  nombre: string
  descripcion: string
  created_at: string
}

export interface Usuario {
  id: number
  nombre: string
  apellido: string
  carne?: string
  correo: string
  password: string
  rol_id: number
  activo: boolean
  created_at: string
  updated_at: string
}

export interface Categoria {
  id: number
  nombre: string
  descripcion: string
  created_at: string
}

export interface Libro {
  id: number
  titulo: string
  autor: string
  editorial?: string
  isbn?: string
  categoria_id: number
  cantidad_total: number
  cantidad_disponible: number
  ubicacion?: string
  descripcion?: string
  fecha_publicacion?: string
  estado: "disponible" | "agotado" | "mantenimiento"
  created_at: string
  updated_at: string
}

export interface Prestamo {
  id: number
  usuario_id: number
  libro_id: number
  fecha_prestamo: string
  fecha_devolucion_esperada: string
  fecha_devolucion_real?: string
  estado: "activo" | "devuelto" | "vencido"
  observaciones?: string
  created_at: string
  updated_at: string
}

// Datos simulados para desarrollo
export const mockRoles: Role[] = [
  { id: 1, nombre: "administrador", descripcion: "Acceso completo al sistema", created_at: "2024-01-01T00:00:00Z" },
  { id: 2, nombre: "bibliotecario", descripcion: "Gestión de libros y préstamos", created_at: "2024-01-01T00:00:00Z" },
  {
    id: 3,
    nombre: "alumno",
    descripcion: "Consulta de libros y préstamos propios",
    created_at: "2024-01-01T00:00:00Z",
  },
]

export const mockUsuarios: Usuario[] = [
  {
    id: 1,
    nombre: "Admin",
    apellido: "Sistema",
    carne: "ADMIN001",
    correo: "admin@biblioteca.edu",
    password: "admin123",
    rol_id: 1,
    activo: true,
    created_at: "2024-01-01T00:00:00Z",
    updated_at: "2024-01-01T00:00:00Z",
  },
  {
    id: 2,
    nombre: "María",
    apellido: "González",
    carne: "BIB001",
    correo: "maria.gonzalez@biblioteca.edu",
    password: "biblio123",
    rol_id: 2,
    activo: true,
    created_at: "2024-01-01T00:00:00Z",
    updated_at: "2024-01-01T00:00:00Z",
  },
  {
    id: 3,
    nombre: "Ana",
    apellido: "López",
    carne: "2024001",
    correo: "ana.lopez@estudiante.edu",
    password: "estudiante123",
    rol_id: 3,
    activo: true,
    created_at: "2024-01-01T00:00:00Z",
    updated_at: "2024-01-01T00:00:00Z",
  },
]

export const mockCategorias: Categoria[] = [
  { id: 1, nombre: "Ficción", descripcion: "Novelas y cuentos", created_at: "2024-01-01T00:00:00Z" },
  { id: 2, nombre: "No Ficción", descripcion: "Libros informativos y educativos", created_at: "2024-01-01T00:00:00Z" },
  {
    id: 3,
    nombre: "Ciencias",
    descripcion: "Libros de ciencias naturales y exactas",
    created_at: "2024-01-01T00:00:00Z",
  },
  { id: 4, nombre: "Historia", descripcion: "Libros de historia y biografías", created_at: "2024-01-01T00:00:00Z" },
  { id: 5, nombre: "Literatura", descripcion: "Clásicos de la literatura", created_at: "2024-01-01T00:00:00Z" },
  {
    id: 6,
    nombre: "Tecnología",
    descripcion: "Libros de informática y tecnología",
    created_at: "2024-01-01T00:00:00Z",
  },
]

export const mockLibros: Libro[] = [
  {
    id: 1,
    titulo: "Cien años de soledad",
    autor: "Gabriel García Márquez",
    editorial: "Editorial Sudamericana",
    isbn: "978-950-07-0001-1",
    categoria_id: 5,
    cantidad_total: 3,
    cantidad_disponible: 2,
    ubicacion: "A-001",
    descripcion: "Obra maestra del realismo mágico",
    fecha_publicacion: "1967-06-05",
    estado: "disponible",
    created_at: "2024-01-01T00:00:00Z",
    updated_at: "2024-01-01T00:00:00Z",
  },
  {
    id: 2,
    titulo: "El principito",
    autor: "Antoine de Saint-Exupéry",
    editorial: "Reynal & Hitchcock",
    isbn: "978-0-15-601219-5",
    categoria_id: 1,
    cantidad_total: 5,
    cantidad_disponible: 4,
    ubicacion: "A-002",
    descripcion: "Clásico de la literatura infantil",
    fecha_publicacion: "1943-04-06",
    estado: "disponible",
    created_at: "2024-01-01T00:00:00Z",
    updated_at: "2024-01-01T00:00:00Z",
  },
  {
    id: 3,
    titulo: "1984",
    autor: "George Orwell",
    editorial: "Secker & Warburg",
    isbn: "978-0-452-28423-4",
    categoria_id: 1,
    cantidad_total: 2,
    cantidad_disponible: 1,
    ubicacion: "A-003",
    descripcion: "Distopía clásica sobre el totalitarismo",
    fecha_publicacion: "1949-06-08",
    estado: "disponible",
    created_at: "2024-01-01T00:00:00Z",
    updated_at: "2024-01-01T00:00:00Z",
  },
]

export const mockPrestamos: Prestamo[] = [
  {
    id: 1,
    usuario_id: 3,
    libro_id: 1,
    fecha_prestamo: "2024-01-15",
    fecha_devolucion_esperada: "2024-01-29",
    estado: "activo",
    created_at: "2024-01-15T00:00:00Z",
    updated_at: "2024-01-15T00:00:00Z",
  },
  {
    id: 2,
    usuario_id: 3,
    libro_id: 3,
    fecha_prestamo: "2024-01-10",
    fecha_devolucion_esperada: "2024-01-24",
    estado: "activo",
    created_at: "2024-01-10T00:00:00Z",
    updated_at: "2024-01-10T00:00:00Z",
  },
]

// Funciones de utilidad para simular operaciones de base de datos
export const getRoleById = (id: number): Role | undefined => {
  return mockRoles.find((role) => role.id === id)
}

export const getUserByEmail = (email: string): Usuario | undefined => {
  return mockUsuarios.find((user) => user.correo === email)
}

export const getCategoriaById = (id: number): Categoria | undefined => {
  return mockCategorias.find((cat) => cat.id === id)
}

export const getLibroById = (id: number): Libro | undefined => {
  return mockLibros.find((libro) => libro.id === id)
}
