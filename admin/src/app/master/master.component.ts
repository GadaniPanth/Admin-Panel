import { Component, HostListener, OnInit } from "@angular/core";
import { AdminService } from "../_services/admin.service";
import { ActivatedRoute, NavigationEnd, Router } from "@angular/router";
import { Title } from "@angular/platform-browser";
import { filter, map, mergeMap } from "rxjs/operators";
import { ToastrService } from "ngx-toastr";

@Component({
  selector: "app-master",
  templateUrl: "./master.component.html",
  styleUrls: ["./master.component.css"],
})
export class MasterComponent implements OnInit {
  constructor(
    public adminService: AdminService,
    private router: Router,
    private route: ActivatedRoute,
    private activatedRoute: ActivatedRoute,
    private titleService: Title,
    private toastr: ToastrService
  ) {
    this.getActiveUser();
  }

  // activeTabName: string = "";
  // inquiryCount: number = 0;

  activeUser: any = {
    name: '',
    email: '',
    designation: '',
    profile_pic: ''
  };

  getActiveUser() {
    this.adminService.get_user_details(this.adminService.getTokenData().user_id).subscribe((res) => {
      this.activeUser.name = res.details.name;
      this.activeUser.email = res.details.email;
      this.activeUser.designation = res.details.designation;
      this.activeUser.profile_pic = res.details.profile_pic ? res.details.profile_pic : 'assets/images/default-user.jpg';
    });
  }

  ngOnInit() {
    this.router.events
      .pipe(filter((event) => event instanceof NavigationEnd))
      .subscribe(() => {
        this.updateActiveMenu();
      });

    this.updateActiveMenu(); // Run initially to set active menu


    // p

    this.permissionList = this.adminService.userpermissions;
    // console.log(this.permissionList);


    for (const key in this.permissionList) {
      if (this.permissionList[key]['can_view'] == 1) {
        this.permissions.push(this.permissionList[key]['slug'])
      }
    }

    //
  }

  permissions: any = []; //
  permissionList: any; //

  updateActiveMenu() {
    let route = this.activatedRoute.firstChild;
    while (route.firstChild) {
      route = route.firstChild; // Navigate to the deepest child
    }

    if (route.snapshot.data) {
      this.titleService.setTitle(`${route.snapshot.data.title} | Admin Panel`);
      this.activeTab = route.snapshot.data["tabName"] || "";
    }
  }

  // changeActiveTab(name: string) {
  //   this.activeTabName = name;
  // }

  hideSideMenuF: boolean = false;
  hideSideMenu() {
    this.hideSideMenuF = !this.hideSideMenuF;
  }

  logout() {
    setTimeout(() => {
      this.router.navigate(["/login"]);
      this.adminService.removeTokenData();
      this.toastr.success('Log out Successfully');
    }, 500);
  }

  activeTab = "home";

  setTab(tab: string) {
    this.activeTab = tab;
  }

  menuVisible = false;

  toggleMenu(event: Event): void {
    event.stopPropagation();
    this.menuVisible = !this.menuVisible;
  }

  openMenu(event: Event): void {
    event.stopPropagation();
    this.menuVisible = true;
  }

  closeMenu(event: Event): void {
    // console.log('hihihi');
    // event.stopPropagation();
    this.menuVisible = false;
  }

  navigateProfile() {
    this.menuVisible = false;
    setTimeout(() => {
      this.router.navigate(['/profile']);
    }, 100);
  }

  @HostListener('document:click', ['$event'])
  onDocumentClick(event: MouseEvent): void {
    const target = event.target as HTMLElement;
    if (!target.closest('.menu-container')) {
      this.menuVisible = false;
    }
  }

  // changePasssFOrms
}
