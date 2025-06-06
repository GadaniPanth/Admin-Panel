import { Injectable } from '@angular/core';
import { HttpInterceptor, HttpRequest, HttpHandler, HttpEvent, HttpResponse, HttpErrorResponse } from '@angular/common/http';
import { Observable, throwError } from 'rxjs';
import { catchError, tap } from 'rxjs/operators';
import { Router } from '@angular/router';
import { AdminService } from '../_services/admin.service';
import Swal from 'sweetalert2';

@Injectable()
export class AuthInterceptor implements HttpInterceptor {

    constructor(private router: Router, private adminService: AdminService) { }

    intercept(req: HttpRequest<any>, next: HttpHandler): Observable<HttpEvent<any>> {

        const token = this.adminService.getTokenData() && this.adminService.getTokenData().token;

        // Clone the request to add the authorization header
        const clonedReq = req.clone({
            setHeaders: {
                Authorization: `${token}`
            }
        });

        // Handle the request and catch errors globally
        return next.handle(clonedReq).pipe(
            catchError((error) => {
                if (error.status === 401) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Session Expired',
                        text: 'Please login again.',
                        confirmButtonColor: "#111111",
                        confirmButtonText: 'OK'
                    }).then(() => {
                        this.adminService.removeTokenData();
                        this.router.navigate(['/login']);
                    });
                }
                return throwError(error); // Re-throw the error
                // throw error;
            })
        );
    }

    // intercept(req: HttpRequest<any>, next: HttpHandler): Observable<HttpEvent<any>> {

    //     // Clone the request to add the authorization token (if available)
    //     const token = this.adminService.getTokenData() ? this.adminService.getTokenData().token : null;

    //     console.log(token);

    //     const clonedRequest = req.clone({
    //         setHeaders: {
    //             Authorization: token ? token : ''
    //         }
    //     });

    //     console.log(clonedRequest);

    //     return next.handle(clonedRequest).pipe(

    //         catchError((error: HttpErrorResponse) => {

    //             console.log(error);

    //             if (error.status === 401) {

    //                 console.log('hello');

    //                 if (this.router.url != '/login') {
    //                     this.adminService.removeTokenData();
    //                 }
    //             }
    //             return throwError(error);
    //         })
    //     );
    // }
}
