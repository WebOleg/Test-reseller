<?php

namespace App\Http\Controllers;

/**
 * @OA\Info(
 *     title="DataImpulse Reseller API",
 *     version="1.0.0",
 *     description="API for managing DataImpulse sub-users and reseller operations",
 *     @OA\Contact(
 *         email="support@example.com"
 *     )
 * )
 * @OA\Server(
 *     url=L5_SWAGGER_CONST_HOST,
 *     description="DataImpulse Reseller API Server"
 * )
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer"
 * )
 */
abstract class Controller
{
    //
}
