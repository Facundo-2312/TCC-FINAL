create database empr;

use empr;

create table Empleado(
CI int primary key  not null,
Nombre varchar(100) not null,
Apellido varchar(100) not null,
Direccion varchar(100) not null,
Rol varchar(50) not null,
Usuario varchar(100) not null,
Pass varchar(100)not null
);