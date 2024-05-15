<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Api;
use Illuminate\Http\Request;
use App\Models\Employee;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class EmployeeController extends Controller
{
    public function index()
    {
        $employees = Employee::all();
        return response()->json($employees);
    }

    public function store(Request $request)
    {
        $validator=Validator::make($request->all(),[
            'first_name' => [
                'required',
                'regex:/^[A-Z\s]+$/',
                'max:20'
            ],
            'last_name' => [
                'required',
                'regex:/^[A-Z\s]+$/',
                'max:20'
            ],
            'second_last_name' => [
                'required',
                'regex:/^[A-Z\s]+$/',
                'max:20'
            ],
            'other_names' => [
                'required',
                'regex:/^[A-Z\s]+$/',
                'max:50'
            ],
            'country' => 'required|in:Colombia,Estados Unidos',
            'identification_type' => 'required|string',
            'identification_number' => 'required|string|max:20|unique:employees',
            'entry_date' => 'required|date',
            'area' => 'required|string',

        ], [
            'first_name.required' => 'El nombre es obligatorio.',
            'first_name.regex' => 'El nombre no debe contener minisculas ni acentos',
            'first_name.max' => 'El nombre no puede tener más de 20 caracteres.',
            'last_name.required' => 'El apellido es obligatorio.',
            'last_name.regex' => 'El apellido no debe contener minisculas ni acentos',
            'last_name.max' => 'El apellido no puede tener más de 20 caracteres.',
            'second_last_name.required' => 'El segundo apellido es obligatorio.',
            'second_last_name.regex' => 'El segundo apellido no debe contener minisculas ni acentos',
            'second_last_name.max' => 'El segundo apellido no puede tener más de 20 caracteres.',
            'other_names.required' => 'Los otros nombres son obligatorios.',
            'other_names.regex' => 'Los otros nombres no debe contener minisculas ni acentos',
            'other_names.max' => 'Los otros nombres no pueden tener más de 50 caracteres.',
        ]);


        
        if ($validator->fails()){
            $data = [
                'message' => 'Error al registrar el empleado',
                'errors' => $validator->errors(),
                'status' => 400
            ];
            return response()->json($data,400);
        }
        // Generar automáticamente el correo electrónico si no se proporciona
        $email = $request->input('email') ?? $this->generateEmail($request);

        $employee = Employee::create([
            'first_name'=>$request->first_name,
            'last_name'=>$request->last_name,
            'second_last_name'=>$request->second_last_name,
            'other_names'=>$request->other_names,
            'country'=>$request->country,
            'identification_type'=>$request->identification_type,
            'identification_number'=>$request->identification_number,
            'email'=>$email, 
            'entry_date'=>$request->entry_date,
            'area'=>$request->area
        ]);
        if(!$employee){
            $data =[
                'message' => 'Error al registrar el empleado',
                'status' => 500
            ];
            return response()->json($data,500);
        }
        $data = [
            'employee'=>$employee,
            'status'=> 201
        ];
        return response()->json($data, 201);
    }
    // Método para generar el correo electrónico automáticamente
    private function generateEmail(Request $request)
{
    // Construir el correo electrónico basado en el nombre y apellido del empleado
    $firstName = $request->input('first_name');
    $lastName = $request->input('last_name');

    // Eliminar espacios en blanco al inicio y al final de los nombres
    $firstName = trim($firstName);
    $lastName = trim($lastName);

    // Convertir los nombres a minúsculas
    $firstNameLower = strtolower($firstName);
    $lastNameLower = strtolower($lastName);

    // Reemplazar espacios en blanco por puntos
    $firstNameLower = str_replace(' ', '', $firstNameLower);
    $lastNameLower = str_replace(' ', '', $lastNameLower);

    // Construir el dominio de correo electrónico según el país
    $domain = ($request->input('country') === 'Colombia') ? 'global.com.co' : 'global.com.us';

    // Construir el correo electrónico final
    $email = $firstNameLower . '.' . $lastNameLower . '@' . $domain;

    // Limitar la longitud del correo electrónico a 300 caracteres
    $email = Str::limit($email, 300);

    // Agregar un sufijo único si el correo electrónico ya existe en la base de datos
    $email = $this->ensureUniqueEmail($email);

    return $email;
}

    // Método para asegurar que el correo electrónico generado sea único
    private function ensureUniqueEmail($email)
    {
        $originalEmail = $email;
        $counter = 1;

        // Separar el correo electrónico en nombre, sufijo y dominio
        list($name, $suffix) = explode('@', $email);

        // Mientras el correo electrónico generado ya exista en la base de datos, agregar un sufijo numérico antes del dominio
        while (Employee::where('email', $email)->exists()) {
            // Construir el correo electrónico con el sufijo numérico antes del dominio
            $email = $name . '.' . $counter . '@' . $suffix;
            $counter++;
        }

        return $email;
    }
    public function show($id)
    {
        $employee = Employee::findOrFail($id);
        return response()->json($employee);
    }

    public function update(Request $request, $id)
{
    $employee = Employee::findOrFail($id);

    // Actualizar los datos del empleado desde el $request
    $employee->update($request->all());

    // Verificar si el nombre o el apellido han cambiado
    $nameChanged = $request->has('first_name') || $request->has('last_name');

    if ($nameChanged) {
        // Generar un nuevo correo electrónico basado en el nombre y apellido actualizados
        $newEmail = $this->generateEmail($request);

        // Actualizar el correo electrónico del empleado con el nuevo valor generado
        $employee->email = $newEmail;
        $employee->save();
    }

    return response()->json($employee);
}
    public function destroy($id)
    {
        $employee = Employee::findOrFail($id);
        $employee->delete();
        return response()->json(null, 204);
    }
}
