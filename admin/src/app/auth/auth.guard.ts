import { Injectable } from '@angular/core';
import { CanActivate, CanActivateChild, CanLoad, Route, UrlSegment, ActivatedRouteSnapshot, RouterStateSnapshot, UrlTree, Router } from '@angular/router';
import { Observable } from 'rxjs';
import { AdminService } from '../_services/admin.service';
import { Location } from '@angular/common';

@Injectable({
  providedIn: 'root'
})
export class AuthGuard implements CanActivate, CanActivateChild, CanLoad {

  constructor(private adminService: AdminService, private router: Router, private location: Location) { }

  canActivate(
    next: ActivatedRouteSnapshot,
    state: RouterStateSnapshot): Observable<boolean | UrlTree> | Promise<boolean | UrlTree> | boolean | UrlTree {

    const isAuthenticated = this.adminService.getTokenData() && this.adminService.getTokenData().token;
    const permissionsData = this.adminService.getTokenData() && this.adminService.getTokenData().permissions; // p


    // p
    const url = state.url;

    if (permissionsData) {
      const haspermission = permissionsData.some((p: any) => {
        if (url.includes(p.slug) && p.can_view == 1) {
          return true;
        }
      });
      if (haspermission) {
        return true;
      }
      else {
        // this.router.navigate(['/adimin/users']);
        // this.location.back();
        // return false;
      }
    }
    //

    if (isAuthenticated) {
      return true;
    }
    else {
      this.router.navigate(['/login']);
      return false;
    }

  }
  canActivateChild(
    next: ActivatedRouteSnapshot,
    state: RouterStateSnapshot): Observable<boolean | UrlTree> | Promise<boolean | UrlTree> | boolean | UrlTree {
    return true;
  }
  canLoad(
    route: Route,
    segments: UrlSegment[]): Observable<boolean> | Promise<boolean> | boolean {
    return true;
  }
}
