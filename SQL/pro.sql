use pro;
drop table Pepc;

create table Pepc(
IDPCP INT primary key not null,
IDPedido INT not null,
IDProducto INT not null,
Cantidad INT not null
);


use pro;
drop table Pedido;

create table Pedido(
IDPedido INT not null primary key auto_increment,
Observaciones  varchar (100) not null,
estado  int  not null,
TipoPago varchar (100),
CI int not null,
Mesa int not null,
Fecha datetime not null,
total double 
);

use pro;
drop table Producto;

create table Producto(
IDProducto int primary key not null,
Nombre varchar(100) not null,
Descripcion varchar(100) not null,
Precio double not null,
Img varchar(150)not null
);