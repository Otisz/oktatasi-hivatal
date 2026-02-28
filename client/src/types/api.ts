export interface Program {
  university: string
  faculty: string
  name: string
}

export interface Applicant {
  id: string
  program: Program
}

export interface ScoreResult {
  osszpontszam: number
  alappont: number
  tobbletpont: number
}

export interface ApiError {
  error: string
}

export interface ApiResponse<T> {
  data: T
}
